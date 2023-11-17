<?php
/**
 * Autoloader.
 *
 * @package pmc-wp-local-data-cli
 */

declare( strict_types = 1 );

namespace PMC\WP_Local_Data_CLI;

/**
 * Class Autoloader.
 */
final class Autoloader {
	private const NS_SEPARATOR = '\\';

	/**
	 * Autoload plugin's classes.
	 *
	 * @param string $class_name Fully-qualified class name.
	 * @return void
	 */
	public static function do( string $class_name ): void {
		if (
			! str_starts_with(
				$class_name,
				__NAMESPACE__ . self::NS_SEPARATOR
			)
		) {
			return;
		}

		$class_name = str_replace(
			[
				__NAMESPACE__ . self::NS_SEPARATOR,
				'_',
			],
			[
				'',
				'-',
			],
			$class_name
		);
		$class_name = strtolower( $class_name );
		$class_name = explode( self::NS_SEPARATOR, $class_name );

		$file_key                = array_key_last( $class_name );
		$class_name[ $file_key ] = sprintf(
			'class-%1$s.php',
			$class_name[ $file_key ]
		);

		$class_name = implode( DIRECTORY_SEPARATOR, $class_name );

		$file_path = sprintf(
			'%2$s%1$sclasses%1$s%3$s',
			DIRECTORY_SEPARATOR,
			__DIR__,
			$class_name
		);

		if ( is_file( $file_path ) && 0 === validate_file( $file_path ) ) {
			// Path is restricted to a particular directory and validated.
			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			require_once $file_path;
		}
	}
}

spl_autoload_register( [ Autoloader::class, 'do' ] );
