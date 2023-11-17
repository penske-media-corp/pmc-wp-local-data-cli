<?php
/**
 * Retain `guest-author` objects.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI\Query_Args;

use PMC\WP_Local_Data_CLI\Query_Args;

/**
 * Class CoAuthors_Plus.
 */
final class CoAuthors_Plus extends Query_Args {
	/**
	 * Skip processing CoAuthor Plus profiles since they have no dependencies.
	 *
	 * @var bool
	 */
	public static bool $find_linked_ids = false;

	/**
	 * Build array of `WP_Query` arguments used to retrieve IDs to retain.
	 *
	 * @return array
	 */
	public static function get_query_args(): array {
		return [
			'post_type' => 'guest-author',
		];
	}
}
