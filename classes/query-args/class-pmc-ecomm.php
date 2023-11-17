<?php
/**
 * Retain post objects that contain meta data for E-Commerce.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\EComm\Disclaimer;
use PMC\Post_Options\Taxonomy;
use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Ecomm.
 */
final class PMC_Ecomm extends Query_Args {
	/**
	 * Skip processing posts flagged as E-Commerce since they have no dependencies.
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
		$term = Disclaimer::get_instance()->get_post_option();

		return [
			'post_type'  => 'any',
			'date_query' => [
				[
					'after' => '-3 months',
				],
			],
			// Used in CLI context.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'  => [
				[
					'taxonomy' => Taxonomy::NAME,
					'field'    => 'slug',
					'terms'    => $term['slug'],
				],
			],
		];
	}
}
