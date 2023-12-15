<?php
/**
 * Retain recent `pmc-gallery` objects and their associated data.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\Gallery\Attachment_Detail;
use PMC\Gallery\Defaults as Plugin;
use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Gallery.
 */
final class PMC_Gallery extends Query_Args {
	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type'  => Plugin::NAME,
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

		self::_add_thumbnail_id( $ids, $id );

		$gallery_images = get_post_meta(
			$id,
			Plugin::NAME,
			true
		);
		if ( is_array( $gallery_images ) ) {
			foreach ( $gallery_images as $attachment_id ) {
				$ids[] = [
					'ID'        => (int) $attachment_id,
					'post_type' => Attachment_Detail::NAME,
				];
			}
		}

		return $ids;
	}
}
