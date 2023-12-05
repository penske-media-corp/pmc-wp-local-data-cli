<?php
/**
 * Perform database cleanup after querying for post IDs to retain.
 *
 * phpcs:disable Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
 * phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
 * phpcs:disable WordPress.DB.DirectDatabaseQuery
 * phpcs:disable WordPress.DB.PreparedSQL
 * phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.user_meta__wpdb__users
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI;

use WP_CLI;
use WPCOM_VIP_Cache_Manager;

/**
 * Class Clean_DB.
 */
final class Clean_DB {
	/**
	 * Clean_DB constructor.
	 */
	public function __construct() {
		$this->_delete_posts();
		$this->_clean_users_table();
		$this->_clean_comments_table();
		$this->_change_admin_email();
	}

	/**
	 * Loop through all posts and delete those that shouldn't be retained.
	 *
	 * @return void
	 */
	private function _delete_posts(): void {
		global $wpdb;

		WP_CLI::line( ' * Starting post deletion. This will take a while...' );

		$page     = 0;
		$per_page = 500;

		$total_ids     = $wpdb->get_var(
			'SELECT COUNT(ID) FROM ' . $wpdb->posts
		);
		$total_batches = ceil( $total_ids / $per_page );

		WP_CLI::line(
			sprintf(
				'   Expecting %1$s batches',
				number_format( $total_batches )
			)
		);

		$total_batches = ceil( $total_ids / $per_page );

		WP_CLI::line(
			sprintf(
				'   Expecting %1$s batches',
				number_format( $total_batches )
			)
		);

		wp_defer_term_counting( true );
		wp_defer_comment_counting( true );

		while (
			$ids = $wpdb->get_col( $this->_get_delete_query( $page, $per_page ) )
		) {
			WP_CLI::line(
				sprintf(
					'   > Processing batch %1$s (%2$d%%)',
					number_format( $page + 1 ),
					round(
						( $page + 1 ) / $total_batches * 100
					)
				)
			);

			foreach ( $ids as $id_to_delete ) {
				wp_delete_post( $id_to_delete, true );
			}

			vip_reset_db_query_log();
			vip_reset_local_object_cache();
			WPCOM_VIP_Cache_Manager::instance()->clear_queued_purge_urls();

			$page++;
		}

		wp_defer_term_counting( false );
		wp_defer_comment_counting( false );

		WP_CLI::line( ' * Finished deleting posts.' );
	}

	/**
	 * Build query to create list of IDs to check against list to retain.
	 *
	 * @param int $page     Current page.
	 * @param int $per_page IDs per page.
	 * @return string
	 */
	private function _get_delete_query( int $page, int $per_page ): string {
		global $wpdb;

		return $wpdb->prepare(
			// Intentionally using complex placeholders to prevent incorrect quoting of table names.
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
			'SELECT ID FROM `%1$s` WHERE ID NOT IN ( SELECT ID FROM `%2$s` ) ORDER BY ID LIMIT %3$d,%4$d',
			$wpdb->posts,
			Init::TABLE_NAME,
			$page * $per_page,
			$per_page
		);
	}

	/**
	 * Remove sensitive data from the users table.
	 *
	 * @return void
	 */
	private function _clean_users_table(): void {
		global $wpdb;

		WP_CLI::line( " * Removing PII from {$wpdb->users}." );

		$wpdb->query( "UPDATE {$wpdb->users} SET user_email='localdev@pmcdev.local';" );
	}

	/**
	 * Remove sensitive data from the comments table.
	 *
	 * @return void
	 */
	private function _clean_comments_table(): void {
		global $wpdb;

		WP_CLI::line( " * Removing PII from {$wpdb->comments}." );

		$wpdb->query( "UPDATE {$wpdb->comments} SET comment_author_email='commenter@pmcdev.local', comment_author_IP='', comment_agent='';" );
	}

	/**
	 * Overwrite admin email used for certain notifications.
	 *
	 * @return void
	 */
	private function _change_admin_email(): void {
		WP_CLI::line( ' * Overwriting `admin_email` option.' );

		update_option( 'admin_email', 'admin@pmcdev.local' );
	}
}
