<?php
/**
 * Retain recent `pmc-nfp` objects and their associated data.
 *
 * As these are similar to `post` objects, this processor extends the one used
 * for those objects so that we capture relevant linked data.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class PMC_Not_For_Publication.
 */
final class PMC_Not_For_Publication extends Post {
	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type'  => 'pmc-nfp',
			'date_query' => [
				[
					'after' => '-9 months',
				],
			],
		];
	}
}
