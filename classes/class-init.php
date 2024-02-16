<?php
/**
 * Commands to prepare a production database backup for use in local development
 * environments.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery
 * phpcs:disable WordPress.DB.PreparedSQL
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI;

use PMC_WP_CLI;
use Throwable;
use WP_CLI;

/**
 * Class Init.
 */
final class Init extends PMC_WP_CLI {
	/**
	 * WP-CLI command slug.
	 */
	public const COMMAND_NAME = 'pmc-local-data';

	/**
	 * Name of table used to hold post IDs to retain.
	 */
	public const TABLE_NAME = 'pmc_local_data_posts_to_keep';

	/**
	 * Prepare production database for use in local-development environment.
	 *
	 * ## EXAMPLES
	 *     wp pmc-local-data start
	 *
	 * @subcommand start
	 *
	 * @return void
	 */
	public function start(): void {
		WP_CLI::line( 'Starting local-data process.' );

		do_action( 'pmc_wp_cli_local_data_before_processing' );

		$this->_drop_table();
		$this->_create_table();

		$this->_query_for_ids_to_keep();

		new Clean_DB();

		do_action( 'pmc_wp_cli_local_data_after_processing' );

		$this->_drop_table();

		WP_CLI::line( 'Process complete.' );
	}

	/**
	 * Create custom table to hold post IDs to retain.
	 *
	 * @return void
	 */
	private function _create_table(): void {
		global $wpdb;

		$wpdb->query(
			'CREATE TABLE IF NOT EXISTS ' . self::TABLE_NAME
			. " (
				ID bigint(20) unsigned NOT NULL,
				post_type varchar(20) NOT NULL,
				PRIMARY KEY  (ID),
				KEY type_id (post_type,ID)
			) {$wpdb->get_charset_collate()};"
		);
	}

	/**
	 * Remove custom table holding post IDs to retain.
	 *
	 * @return void
	 */
	private function _drop_table(): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::TABLE_NAME );
	}

	/**
	 * Retrieve number of post-object IDs identified for retention.
	 *
	 * @return int
	 */
	private function _count_ids_to_keep(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			'SELECT COUNT(ID) FROM ' . self::TABLE_NAME
		);
	}

	/**
	 * Run all configured queries to build list of post-object IDs to retain.
	 *
	 * @return void
	 */
	private function _query_for_ids_to_keep(): void {
		$query_args_instances = $this->_get_query_args_instances();

		foreach ( $query_args_instances as $query_args_instance ) {
			new Query( $query_args_instance );
		}

		/**
		 * Backfill is processed after all initial IDs are gathered in case
		 * linked IDs themselves have dependencies.
		 *
		 * This process may run multiple times because a run of backfill could
		 * add additional IDs that themselves have dependencies.
		 */
		do {
			$found_ids = $this->_count_ids_to_keep();

			foreach ( $query_args_instances as $query_args_instance ) {
				if ( $query_args_instance::$skip_backfill ) {
					continue;
				}

				new Query( $query_args_instance, true );
			}
		} while ( $found_ids !== $this->_count_ids_to_keep() );

		WP_CLI::line(
			sprintf(
				'   > Will retain %1$s IDs.',
				number_format_i18n( $found_ids, 0 )
			)
		);
	}

	/**
	 * Provide query arguments and optional callbacks used to identify post IDs
	 * to retain.
	 *
	 * @return array
	 */
	private function _get_query_args_instances(): array {
		$classes = [
			Query_Args\CoAuthors_Plus::class,
			Query_Args\Nav_Menu_Item::class,
			Query_Args\OEmbed_Cache::class,
			Query_Args\Page::class,
			Query_Args\PMC_Ads::class,
			Query_Args\PMC_Amzn_Onsite::class,
			Query_Args\PMC_Attachments::class,
			Query_Args\PMC_Buy_Now_Block::class,
			Query_Args\PMC_Buy_Now_Shortcode::class,
			Query_Args\PMC_Carousel::class,
			Query_Args\PMC_Ecomm::class,
			Query_Args\PMC_FAQ::class,
			Query_Args\PMC_Gallery::class,
			Query_Args\PMC_Hub::class,
			Query_Args\PMC_List::class,
			Query_Args\PMC_Not_For_Publication::class,
			Query_Args\PMC_Profiles::class,
			Query_Args\PMC_Profiles_Landing_Page::class,
			// Query_Args\PMC_Publication_Issue::class,
			Query_Args\PMC_Reviews::class,
			Query_Args\PMC_Store_Products::class,
			Query_Args\PMC_TOC::class,
			Query_Args\PMC_Top_Video::class,
			Query_Args\PMC_Touts::class,
			Query_Args\Post::class,
			Query_Args\Safe_Redirect_Manager::class,
			Query_Args\WPCOM_Legacy_Redirector::class,
			Query_Args\Zoninator::class,
		];

		$query_args = [];

		/**
		 * Each built-in class is instantiated in a try-catch block because some
		 * of our plugins don't support the autoloader in
		 * `pmc-global-functions`, and if a theme doesn't activate one of the
		 * plugins that a `Query_Args` class depends on, a fatal error may be
		 * thrown.
		 *
		 * We can safely assume that if the active theme doesn't load an
		 * affected plugin, its data is not required for local development.
		 * Rather than stop the cleanup process, the try-catch allows us to
		 * surface the error and carry on.
		 */
		foreach ( $classes as $class ) {
			try {
				$query_args[] = new $class();
			} catch ( Throwable $throwable ) {
				WP_CLI::warning( $throwable->getMessage() );
			}
		}

		/**
		 * Allow themes to add support for custom features that are not part of
		 * `pmc-plugins`.
		 *
		 * @param array $instances Instantiated classes that extend the
		 *                         `Query_Args` base class.
		 */
		$additional_instances = apply_filters(
			'pmc_wp_cli_local_data_query_args_instances',
			[]
		);

		return array_filter(
			array_merge(
				$query_args,
				$additional_instances
			),
			static fn ( mixed $instance ): bool =>
				$instance instanceof Query_Args
		);
	}
}
