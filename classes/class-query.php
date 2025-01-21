<?php
/**
 * Query the posts table to build list of IDs to retain based on provided
 * `WP_Query` arguments and an optional callback.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI;

use WP_CLI;
use WP_Query;

/**
 * Class Query.
 */
final class Query {
	/**
	 * Query constructor.
	 *
	 * @param Query_Args $instance         Instance of `Query_Args` class.
	 * @param bool       $process_backfill Backfill recursive dependent IDs for
	 *                                     IDs already queried.
	 */
	public function __construct(
		Query_Args $instance,
		bool $process_backfill = false
	) {
		$args = $process_backfill
			? $instance::get_query_args_for_backfill()
			: $instance::get_query_args();

		if ( ! isset( $args['post_type'] ) ) {
			WP_CLI::error(
				'Invalid configuration: ' . wp_json_encode( $args )
			);
		}

		WP_CLI::line(
			sprintf(
				$process_backfill
				? ' * Backfilling IDs using `%1$s` query args.'
				: ' * Gathering IDs using `%1$s`.',
				str_replace(
					__NAMESPACE__ . '\\',
					'',
					$instance::class
				)
			)
		);

		$this->_query(
			$args,
			$instance::$find_linked_ids
				? [ $instance::class, 'get_linked_ids' ]
				: null,
		);
	}

	/**
	 * Gather IDs to retain.
	 *
	 * @param array      $args     Query arguments.
	 * @param array|null $callback Callback to apply to found IDs.
	 * @return void
	 */
	private function _query(
		array $args,
		?array $callback
	): void {
		$query_args = wp_parse_args(
			[
				'cache_results'          => true,
				'fields'                 => 'ids',
				'ignore_sticky_posts'    => true,
				'lazy_load_term_meta'    => false,
				'no_found_rows'          => true,
				'paged'                  => 1,
				// Used only in CLI context, higher value allowed.
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
				'posts_per_page'         => 500,
				'post_status'            => 'any',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'suppress_filters'       => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'update_menu_item_cache' => false,
			],
			$args
		);

		$query = new WP_Query( $query_args );

		do {
			foreach ( $query->posts as $id ) {
				$this->_write_id_to_db( $id, get_post_type( $id ) );

				if ( has_blocks( $id ) ) {
					$ids = ( new Gutenberg( $id ) )->get_ids();
					foreach ( $ids as $gutenberg_id ) {
						$this->_write_id_to_db(
							$gutenberg_id['ID'],
							$gutenberg_id['post_type']
						);
					}
				}

				if ( null === $callback ) {
					continue;
				}

				foreach (
					$callback( $id, get_post_type( $id ) ) as $entry
				) {
					$this->_write_id_to_db( $entry['ID'], $entry['post_type'] );
				}
			}

			$query_args['paged']++;
			$query = new WP_Query( $query_args );
		} while ( $query->have_posts() );
	}

	/**
	 * Save to custom database table the post IDs to retain.
	 *
	 * @param int    $id        Post ID.
	 * @param string $post_type Post type.
	 * @return void
	 */
	private function _write_id_to_db( int $id, string $post_type ): void {
		global $wpdb;

		if ( 'any' === $post_type ) {
			$post_type = get_post_type( $id );
		}

		// TODO: how do we end up with empty `post_type`?
		if ( empty( $id ) || empty( $post_type ) ) {
			return;
		}

		$wpdb->insert(
			Init::TABLE_NAME,
			[
				'ID'        => $id,
				'post_type' => $post_type,
			],
			[
				'ID'        => '%d',
				'post_type' => '%s',
			]
		);
	}
}
