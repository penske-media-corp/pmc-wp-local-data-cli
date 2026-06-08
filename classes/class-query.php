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
				'cache_results'          => false,
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

		$buffer = [];
		$query  = new WP_Query( $query_args );

		do {
			foreach ( $query->posts as $id ) {
				$buffer[] = [
					'ID'        => $id,
					'post_type' => get_post_type( $id ),
				];

				if ( has_blocks( $id ) ) {
					$ids = ( new Gutenberg( $id ) )->get_ids();
					foreach ( $ids as $gutenberg_id ) {
						$buffer[] = $gutenberg_id;
					}
				}

				if ( null !== $callback ) {
					foreach (
						$callback( $id, get_post_type( $id ) ) as $entry
					) {
						$buffer[] = $entry;
					}
				}

				if ( count( $buffer ) >= 500 ) {
					$this->_flush_buffer( $buffer );
					$buffer = [];
				}
			}

			$query_args['paged']++;
			$query = new WP_Query( $query_args );
		} while ( $query->have_posts() );

		if ( ! empty( $buffer ) ) {
			$this->_flush_buffer( $buffer );
		}
	}

	/**
	 * Flush a buffer of ID/post_type pairs to the keep table in one INSERT.
	 *
	 * @param array $buffer Array of ['ID' => int, 'post_type' => string].
	 * @return void
	 */
	private function _flush_buffer( array $buffer ): void {
		global $wpdb;

		$rows         = [];
		$placeholders = [];

		foreach ( $buffer as $entry ) {
			$id        = (int) $entry['ID'];
			$post_type = $entry['post_type'];

			if ( 'any' === $post_type ) {
				$post_type = get_post_type( $id );
			}

			// TODO: how do we end up with empty `post_type`?
			if ( empty( $id ) || empty( $post_type ) ) {
				continue;
			}

			$rows[]         = $id;
			$rows[]         = $post_type;
			$placeholders[] = '(%d, %s)';
		}

		if ( empty( $placeholders ) ) {
			return;
		}

		$table = Init::TABLE_NAME;
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sql   = 'INSERT IGNORE INTO ' . $table . ' (ID, post_type) VALUES '
				. implode( ', ', $placeholders );

		$wpdb->query( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			$wpdb->prepare( $sql, ...$rows ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}
}
