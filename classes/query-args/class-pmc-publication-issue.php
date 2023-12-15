<?php
/**
 * Retain recent `pmc-publication-issue` objects and their associated data.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use O2O_Query;
use PMC\Publication_Issue_V2\Publication_Issue;
use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Publication_Issue.
 */
final class PMC_Publication_Issue extends Query_Args {
	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		$query = [
			'post_type'  => Publication_Issue::POST_TYPE,
			'date_query' => [
				[
					'after' => '-6 months',
				],
			],
		];

		// Short-circuit this class if plugin isn't loaded.
		if ( ! class_exists( O2O_Query::class, false ) ) {
			self::$skip_backfill = true;

			// Used in CLI context.
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$query['meta_query'] = [
				[
					'key'   => 'noop',
					'value' => true,
				],
			];

			return $query;
		}

		return $query;
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

		$pdf = (int) get_post_meta(
			$id,
			'pmc-pub-issue_pdf_attachment_attachment_id',
			true
		);
		if ( ! empty( $pdf ) ) {
			$ids[] = [
				'ID'        => $pdf,
				'post_type' => 'attachment',
			];
		}

		$lead_article = (int) get_post_meta( $id, 'lead_article_id', true );
		if ( ! empty( $lead_article ) ) {
			$ids[] = [
				'ID'        => $lead_article,
				'post_type' => get_post_type( $lead_article ),
			];
		}

		$issue_posts = Publication_Issue::get_instance()->get_posts(
			[
				'issue_id'       => $id,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'post_type'      => 'any',
			]
		);
		if ( is_array( $issue_posts ) ) {
			foreach ( $issue_posts as $issue_post ) {
				$ids[] = [
					'ID'        => $issue_post->ID,
					'post_type' => $issue_post->post_type,
				];
			}
		}

		return $ids;
	}
}
