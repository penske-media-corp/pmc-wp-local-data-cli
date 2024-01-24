<?php
/**
 * Augment data cleanup with PMC-specific behaviours.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI;

use PMC\Global_Functions\Traits\Singleton;
use PMC\Global_Functions\VIP_Go_Sync_Cleanup;
use WP_CLI;

/**
 * Class Customizations.
 */
final class Customizations {
	use Singleton;

	/**
	 * Customizations constructor.
	 */
	private function __construct() {
		$this->_setup_hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	private function _setup_hooks(): void {
		add_action(
			'pmc_wp_cli_local_data_before_processing',
			static function (): void {
				add_filter( 'pre_wp_mail', '__return_false' );
			},
			PHP_INT_MIN
		);
		add_action(
			'pmc_wp_cli_local_data_after_processing',
			static function (): void {
				remove_filter( 'pre_wp_mail', '__return_false' );
			},
			PHP_INT_MAX
		);

		add_action(
			'pmc_wp_cli_local_data_before_processing',
			[ $this, 'remove_sensitive_data' ]
		);

		add_action(
			'pmc_wp_cli_local_data_before_processing',
			[ $this, 'disconnect_jetpack' ]
		);

		add_action(
			'pmc_wp_cli_local_data_after_processing',
			[ $this, 'add_dev_user' ]
		);

		add_action(
			'pmc_wp_cli_local_data_after_processing',
			[ $this, 'rebuild_sitemaps' ]
		);

		add_action(
			'pmc_wp_cli_local_data_after_processing',
			[ $this, 'flush_rewrites' ]
		);

		add_action(
			'pmc_wp_cli_local_data_after_processing',
			[ $this, 'remove_google_analytics_ids' ]
		);
	}

	/**
	 * Run sensitive-data cleanup that should be performed before all other
	 * database manipulations.
	 *
	 * @return void
	 */
	public function remove_sensitive_data(): void {
		WP_CLI::line(
			' * Removing sensitive data, such as API keys, before trimming database.'
		);

		VIP_Go_Sync_Cleanup::get_instance()->do_cleanup();
	}

	/**
	 * Remove Jetpack connection data.
	 *
	 * @return void
	 */
	public function disconnect_jetpack(): void {
		WP_CLI::line( ' * Disconnecting Jetpack.' );

		foreach (
			[
				'jetpack_active_plan',
				'jetpack_options',
				'jetpack_private_options',
			] as $option
		) {
			delete_option( $option );
		}
	}

	/**
	 * Add our default local user.
	 *
	 * @return void
	 */
	public function add_dev_user(): void {
		wp_insert_user(
			[
				'user_login' => 'pmcdev',
				'user_pass'  => 'pmcdev',
				'user_email' => 'pmcdev@pmc.local',
				'role'       => 'administrator',
			]
		);

		WP_CLI::line( ' * Added `pmcdev` user.' );
	}

	/**
	 * Rebuild sitemaps to contain only post objects present in trimmed data.
	 *
	 * @return void
	 */
	public function rebuild_sitemaps(): void {
		// TODO: implement. Not as simple as it seems.
		WP_CLI::line(
			' * Sitemap regeneration has not been implemented. Refer to plugin\'s WP-CLI commands.'
		);
	}

	/**
	 * Flush rewrite rules so that option is populated.
	 *
	 * @return void
	 */
	public function flush_rewrites(): void {
		WP_CLI::line( ' * Flushing rewrite rules.' );

		// Used in CLI context.
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		flush_rewrite_rules( false );
		do_action( 'rri_flush_rules' );
	}

	/**
	 * Clear options holding Google Analytics IDs.
	 *
	 * @return void
	 */
	public function remove_google_analytics_ids(): void {
		WP_CLI::line( ' * Removing Google Analytics IDs.' );

		foreach (
			[
				'pmc_ga4_admin_tracking_id',
				'pmc_ga4_newsbreak_tracking_id',
				'pmc_google_analytics_account',
				'pmc_google_analytics_account_ga4',
				'pmc_google_tag_manager_account',
			] as $option
		) {
			delete_option( $option );
		}
	}
}
