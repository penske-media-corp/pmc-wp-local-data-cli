<?php
/**
 * Retain BuddyPress email objects.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class BuddyPress_Email.
 */
// phpcs:ignore PEAR.NamingConventions.ValidClassName.StartWithCapital, Squiz.Commenting.ClassComment.Missing
final class BuddyPress_Email extends Query_Args {
	/**
	 * Email objects have no dependent data.
	 *
	 * @var bool
	 */
	public static bool $find_linked_ids = false;

	/**
	 * Backfill is not required.
	 *
	 * @var bool
	 */
	public static bool $skip_backfill = true;

	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		// Short-circuit this handler when post type is unknown.
		if ( ! function_exists( 'bp_get_email_post_type' ) ) {
			return [
				'post_type'  => 'abcdef0123456789',
				'date_query' => [
					[
						'after' => '+500 years',
					],
				],
			];
		}

		return [
			'post_type' => bp_get_email_post_type(),
		];
	}
}
