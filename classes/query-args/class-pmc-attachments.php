<?php
/**
 * Retain recent `pmc-attachments` objects and their associated data. These
 * objects are part of the `pmc-gallery` plugins.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\Gallery\Attachment_Detail;
use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Attachments.
 */
final class PMC_Attachments extends Query_Args {
	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type'  => Attachment_Detail::NAME,
			'date_query' => [
				[
					'after' => '-1 months',
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

		$ids[] = [
			'ID'        => get_post_field( 'post_parent', $id ),
			'post_type' => 'attachment',
		];

		return $ids;
	}
}
