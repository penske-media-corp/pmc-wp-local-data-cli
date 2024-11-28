<?php
/**
 * Plugin Name:       PMC WP Local Data CLI
 * Plugin URI:        https://pmc.com/
 * Description:       WP-CLI commands to trim production database backup for local development.
 * Version:           0.1.0
 * Requires at least: 6.2
 * Requires PHP:      8.2
 * Text Domain:       pmc-wp-local-data-cli
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI;

use WP_CLI;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Domain used in various replacements.
 */
const LOCAL_DOMAIN = 'pmcdev.local';

require_once __DIR__ . '/dependencies.php';
require_once __DIR__ . '/autoloader.php';

Customizations::get_instance();
WP_CLI::add_command( Init::COMMAND_NAME, Init::class );
