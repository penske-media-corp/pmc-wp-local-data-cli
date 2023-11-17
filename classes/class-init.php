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
		$query_args = [
			new Query_Args\CoAuthors_Plus(),
			new Query_Args\Nav_Menu_Item(),
			new Query_Args\OEmbed_Cache(),
			new Query_Args\Page(),
			new Query_Args\PMC_Ads(),
			new Query_Args\PMC_Amzn_Onsite(),
			new Query_Args\PMC_Attachments(),
			new Query_Args\PMC_Buy_Now_Block(),
			new Query_Args\PMC_Buy_Now_Shortcode(),
			new Query_Args\PMC_Carousel(),
			new Query_Args\PMC_Ecomm(),
			new Query_Args\PMC_FAQ(),
			new Query_Args\PMC_Gallery(),
			new Query_Args\PMC_Hub(),
			new Query_Args\PMC_List(),
			new Query_Args\PMC_Not_For_Publication(),
			new Query_Args\PMC_Profiles(),
			new Query_Args\PMC_Profiles_Landing_Page(),
			new Query_Args\PMC_Publication_Issue(),
			new Query_Args\PMC_Reviews(),
			new Query_Args\PMC_Store_Products(),
			new Query_Args\PMC_TOC(),
			new Query_Args\PMC_Top_Video(),
			new Query_Args\PMC_Touts(),
			new Query_Args\Post(),
			new Query_Args\Safe_Redirect_Manager(),
			new Query_Args\WPCOM_Legacy_Redirector(),
			new Query_Args\Zoninator(),
		];

		$additional_instances = apply_filters(
			'pmc_wp_cli_local_data_query_args_instances',
			[]
		);

		return array_merge( $query_args, $additional_instances );
	}
}
