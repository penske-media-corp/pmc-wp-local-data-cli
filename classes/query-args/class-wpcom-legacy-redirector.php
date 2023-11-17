<?php
/**
 * Retain `vip-legacy-redirect` objects.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class WPCOM_Legacy_Redirector.
 */
final class WPCOM_Legacy_Redirector extends Query_Args {
	/**
	 * Skip processing redirects since they have no dependencies.
	 *
	 * @var bool
	 */
	public static bool $find_linked_ids = false;

	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type' => 'vip-legacy-redirect',
		];
	}
}
