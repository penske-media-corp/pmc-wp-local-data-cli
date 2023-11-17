<?php
/**
 * Retain recent `pmc-carousel` objects and their associated data.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;
use PMC_Master_Featured_Articles as Plugin;

/**
 * Class PMC_Carousel.
 */
final class PMC_Carousel extends Query_Args {
	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type' => Plugin::get_instance()->featured_post_type,
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

		$linked_article = get_post_meta( $id, '_pmc_master_article_id', true );

		if ( is_numeric( $linked_article ) ) {
			$ids[] = [
				'ID'        => $linked_article,
				'post_type' => get_post_type( $linked_article ),
			];
		} else {
			$linked_article = json_decode( stripslashes( $linked_article ) );

			if (
				is_object( $linked_article )
				&& isset( $linked_article->id )
				&& (
					! isset( $linked_article->type )
					|| 'Article' === $linked_article->type
				)
			) {
				$ids[] = [
					'ID'        => $linked_article->id,
					'post_type' => get_post_type( $linked_article->id ),
				];
			}
		}

		return $ids;
	}
}
