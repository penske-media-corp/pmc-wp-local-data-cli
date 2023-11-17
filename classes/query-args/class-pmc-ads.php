<?php
/**
 * Retain `pmc-ad` objects.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;
use PMC_Ads as Plugin;

/**
 * Class PMC_Ads.
 */
final class PMC_Ads extends Query_Args {
	/**
	 * Skip processing ads for linked data as they have none.
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
			'post_type' => Plugin::POST_TYPE,
		];
	}
}
