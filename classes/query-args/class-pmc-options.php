<?php
/**
 * Retain `pmc-long-options` objects.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;
use PMC_Options as Plugin;

/**
 * Class PMC_Options.
 */
final class PMC_Options extends Query_Args {
	/**
	 * Skip processing options for linked data as usage is not predictable. Most
	 * relevant data is held in postmeta and will be retained automatically.
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
			'post_type' => Plugin::POST_TYPE_NAME,
		];
	}
}
