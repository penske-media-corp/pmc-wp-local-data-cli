<?php
/**
 * Retain recent `pmc_list` objects and their associated data.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\Gallery\Lists;
use PMC\Gallery\Lists_Settings;
use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_List.
 */
final class PMC_List extends Query_Args {
	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type'  => Lists_Settings::LIST_POST_TYPE,
			'date_query' => [
				[
					'after' => '-3 months',
				],
			],
		];
	}

	/**
	 * Process dependent data associated with a given ID, such as a post's
	 * thumbnail.
	 *
	 * If post type has no dependent data, set `static::$find_linked_ids` to
	 * false to skip this method.
	 *
	 * @param int    $id        Post ID.
	 * @param string $post_type Post type of given ID.
	 * @return array
	 */
	// Declaration must be compatible with overridden method.
	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassAfterLastUsed, Squiz.Commenting.FunctionComment.Missing, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	public static function get_linked_ids( int $id, string $post_type ): array {
		$ids = [];

		$list_items = Lists::get_instance()->get_sorted_list_item_ids( $id );
		// Variable is known to be an array.
		// phpcs:ignore PmcWpVip.Functions.StrictArrayParameters.NoTypeCastParam
		$list_items = array_map( 'intval', $list_items );

		foreach ( $list_items as $item ) {
			$ids[] = [
				'ID'        => $item,
				'post_type' => get_post_type( $item ),
			];

			self::_add_thumbnail_id( $ids, $item );
		}

		return $ids;
	}
}
