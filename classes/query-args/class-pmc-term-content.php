<?php
/**
 * Retain post objects that use the PMC Term Content plugin.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\Term_Content\Plugin;
use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Term_Content.
 */
final class PMC_Term_Content extends Query_Args {
	/**
	 * Skip processing backfill, deferring to post-type-specific handling.
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
			'post_type'      => Plugin::get_instance()->get_post_type_slugs(),
			'posts_per_page' => -1,
		];
	}
}
