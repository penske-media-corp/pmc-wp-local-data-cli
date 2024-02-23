<?php
/**
 * Retain `pmc-custom-feed` objects.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;
use PMC_Custom_Feed as Plugin;

/**
 * Class PMC_Custom_Feed.
 */
final class PMC_Custom_Feed extends Query_Args {
	/**
	 * Skip processing custom feed objects since they have no dependencies.
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
			'post_type'      => Plugin::post_type_name,
			'posts_per_page' => - 1,
		];
	}
}