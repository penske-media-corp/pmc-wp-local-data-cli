<?php
/**
 * Retain post objects that contain meta data for store products.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Store_Products.
 */
final class PMC_Store_Products extends Query_Args {
	/**
	 * Skip processing posts flagged with store products since they have no dependencies.
	 *
	 * @var bool
	 */
	public static bool $find_linked_ids = false;

	/**
	 * Whether or not to skip the backfill process. Typically this is used when
	 * a query's `post_type` is set to the special value "any" as backfill
	 * cannot be applied to such a query.
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
		return [
			'post_type'  => 'any',
			'date_query' => [
				[
					'after' => '-3 months',
				],
			],
			// Used in CLI context.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => 'amazon_product_references',
					'compare' => 'EXISTS',
				],
				[
					'key'     => 'amazon_product_references',
					'value'   => 'a:0:{}', // Empty array.
					'compare' => '!=',
				],
			],
		];
	}
}