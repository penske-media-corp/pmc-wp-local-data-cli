<?php
/**
 * Retain recent `touts` objects and their associated data.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\Touts\Tout;
use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Touts.
 */
final class PMC_Touts extends Query_Args {
	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type'  => Tout::POST_TYPE_NAME,
			'date_query' => [
				[
					'after' => '-6 months',
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

		self::_add_thumbnail_id( $ids, $id );

		$linked_url = get_post_meta( $id, 'tout_link', true );
		if ( ! empty( $linked_url ) ) {
			$post_id = wpcom_vip_url_to_postid( $linked_url );

			if ( ! empty( $post_id ) ) {
				$ids[] = [
					'ID'        => $post_id,
					'post_type' => get_post_type( $post_id ),
				];
			}
		}

		return $ids;
	}
}
