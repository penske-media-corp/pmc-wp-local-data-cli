<?php
/**
 * Retain `pmc_profiles` (or customized slug) objects.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\Gallery\Defaults as PMC_Gallery;
use PMC\PMC_Profiles\Post_Type;
use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Profiles.
 */
final class PMC_Profiles extends Query_Args {
	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type' => Post_Type::get_instance()->get_profile_post_type_slug(),
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

		$linked_gallery_meta = get_post_meta(
			$id,
			'pmc-gallery-linked-gallery',
			true
		);
		if ( $linked_gallery_meta ) {
			if ( is_string( $linked_gallery_meta ) ) {
				$linked_gallery_meta = json_decode(
					$linked_gallery_meta,
					true
				);
			}

			$ids[] = [
				'ID'        => (int) $linked_gallery_meta['id'],
				'post_type' => PMC_Gallery::NAME,
			];
		}

		return $ids;
	}
}
