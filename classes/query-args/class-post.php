<?php
/**
 * Retain recent `post` objects and their associated data.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\Gallery\Defaults as PMC_Gallery;
use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class Post.
 */
// This class is a rare case where it's okay to be extended by other classes.
// phpcs:ignore SlevomatCodingStandard.Classes.RequireAbstractOrFinal.ClassNeitherAbstractNorFinal, Squiz.Commenting.ClassComment.Missing
class Post extends Query_Args {
	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type'  => 'post',
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

		static::_add_thumbnail_id( $ids, $id );

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
