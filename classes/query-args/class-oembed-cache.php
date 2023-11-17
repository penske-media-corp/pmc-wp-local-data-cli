<?php
/**
 * Retain `oembed_cache` objects.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class OEmbed_Cache.
 */
final class OEmbed_Cache extends Query_Args {
	/**
	 * Skip processing these objects  for linked data as have no dependencies.
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
			'post_type' => 'oembed_cache',
		];
	}
}
