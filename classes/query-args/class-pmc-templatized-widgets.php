<?php
/**
 * Retain post objects that contain configuration and data for the PMC
 * Templatized Widgets plugin.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Templatized_Widgets.
 */
final class PMC_Templatized_Widgets extends Query_Args {
	/**
	 * Skip further processing of widget configuration objects as they have no
	 * dependencies not already retained by this class.
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
			'post_type' => [
				'pmc_widget_data',
				'pmc_widget_template',
			],
		];
	}
}
