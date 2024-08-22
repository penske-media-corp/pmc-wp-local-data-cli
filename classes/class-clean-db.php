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
use WP_Post;
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
		$this->_clean_usermeta_table();
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
			"SELECT COUNT(ID) FROM `{$wpdb->posts}` WHERE post_type != 'revision'"
		);
		$total_to_keep = $wpdb->get_var(
			'SELECT COUNT(ID) FROM ' . Init::TABLE_NAME
		);
		$total_batches = ceil( ( $total_ids - $total_to_keep ) / $per_page );
		WP_CLI::line(
			sprintf(
				'   Expecting %1$s batches (%2$s total IDs; %3$s to keep; deleting %4$s per batch)',
				number_format_i18n( $total_batches ),
				number_format_i18n( $total_ids ),
				number_format_i18n( $total_to_keep ),
				number_format_i18n( $per_page )
			)
		);

		$this->_defer_counts( true );

		while (
			$ids = $wpdb->get_col( $this->_get_delete_query( $per_page ) )
		) {
			if ( $page > ( $total_batches * 1.25 ) ) {
				WP_CLI::warning(
					sprintf(
						'   > Infinite loop detected, terminating deletion with at least %1$s IDs left to delete!',
						number_format_i18n(
							count( $ids )
						)
					)
				);

				break;
			}

			WP_CLI::line(
				sprintf(
					'   > Processing batch %1$s (%2$d%%)',
					number_format_i18n( $page + 1 ),
					round(
						( $page + 1 ) / $total_batches * 100
					)
				)
			);

			foreach ( $ids as $id_to_delete ) {
				$deleted = wp_delete_post( $id_to_delete, true );

				if ( ! $deleted instanceof WP_Post ) {
					WP_CLI::warning(
						sprintf(
							'     - Failed to delete post ID `%1$d`',
							$id_to_delete
						)
					);
				}
			}

			$this->_free_resources();

			$page++;
		}

		$this->_free_resources();
		$this->_defer_counts( false );

		WP_CLI::line( ' * Finished deleting posts.' );
	}

	/**
	 * Prevent WP from performing certain counting operations.
	 *
	 * @param bool $defer To defer or not to defer, that is the question.
	 * @return void
	 */
	private function _defer_counts( bool $defer ): void {
		wp_defer_term_counting( $defer );
		wp_defer_comment_counting( $defer );
	}

	/**
	 * Build query to create list of IDs to check against list to retain.
	 *
	 * @param int $per_page IDs per page.
	 * @return string
	 */
	private function _get_delete_query( int $per_page ): string {
		global $wpdb;

		return $wpdb->prepare(
			// Intentionally using complex placeholders to prevent incorrect quoting of table names.
			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnquotedComplexPlaceholder
			'SELECT ID FROM `%1$s` WHERE ID NOT IN ( SELECT ID FROM `%2$s` ) AND post_type != \'revision\' ORDER BY ID ASC LIMIT %3$d,%4$d',
			$wpdb->posts,
			Init::TABLE_NAME,
			0,
			$per_page
		);
	}

	/**
	 * Perform operations to free resources.
	 *
	 * @return void
	 */
	private function _free_resources(): void {
		vip_reset_db_query_log();
		vip_reset_local_object_cache();
		WPCOM_VIP_Cache_Manager::instance()->clear_queued_purge_urls();
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
	 * Remove sensitive data from the usermeta table.
	 *
	 * @return void
	 */
	private function _clean_usermeta_table(): void {
		global $wpdb;

		WP_CLI::line( " * Removing PII from {$wpdb->usermeta}." );

		// Session tokens include users' IP address.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s;",
				'session_tokens'
			)
		);
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
		delete_option( 'new_admin_email' );
	}
}
