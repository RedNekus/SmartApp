<?php
/**
 * Configuration Loader - .env support for Company Contact Form
 *
 * @package Company Contact Form
 * @since   1.3.0
 */

namespace CCF;

/**
 * Config class for loading environment variables.
 *
 * Supports .env file in plugin root or wp-config.php constants.
 * Priority: wp-config.php constants (non-empty) > .env file > default value.
 */
class Config {

	/**
	 * Loaded variables cache.
	 *
	 * @var array<string, mixed>
	 */
	private static $vars = array();

	/**
	 * Load .env file if exists.
	 *
	 * Called early in plugin bootstrap.
	 * Parses KEY=VALUE pairs and caches CCF_* and SMTP_* variables.
	 *
	 * @return void
	 */
	public static function load() {
		$env_file = defined( 'CCF_PATH' ) ? CCF_PATH . '.env' : null;

		if ( ! $env_file || ! file_exists( $env_file ) ) {
			return;
		}

		$lines = file( $env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		foreach ( $lines as $line ) {
			$trimmed = trim( $line );

			// Skip comments and empty lines.
			if ( '' === $trimmed || '#' === $trimmed[0] ) {
				continue;
			}

			// Parse KEY=VALUE.
			if ( false !== strpos( $line, '=' ) ) {
				list( $key, $value ) = explode( '=', $line, 2 );
				$key                 = trim( $key );
				$value               = trim( $value, " \t\n\r\0\x0B\"'" );

				// Only load CCF_* and SMTP_* vars.
				if ( 0 === strpos( $key, 'CCF_' ) || 0 === strpos( $key, 'SMTP_' ) ) {
					self::$vars[ $key ] = $value;
				}
			}
		}
	}

	/**
	 * Get config value with fallback.
	 *
	 * Priority:
	 * 1. wp-config.php constant (if defined AND non-empty)
	 * 2. .env file value
	 * 3. Default value
	 *
	 * @param string $key     Config key.
	 * @param mixed  $def Default value if not found.
	 * @return mixed
	 */
	public static function get( $key, $def = null ) {
		// 1. Check wp-config.php constant (only if non-empty).
		if ( defined( $key ) ) {
			$value = constant( $key );
			if ( '' !== $value && null !== $value && false !== $value ) {
				return $value;
			}
		}

		// 2. Check loaded .env vars.
		if ( isset( self::$vars[ $key ] ) ) {
			return self::$vars[ $key ];
		}

		// 3. Return default.
		return $def;
	}

	/**
	 * Get boolean config value.
	 *
	 * @param string $key     Config key.
	 * @param bool   $def Default value.
	 * @return bool
	 */
	public static function get_bool( $key, $def = false ) {
		$value = self::get( $key, $def );
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Get integer config value.
	 *
	 * @param string $key     Config key.
	 * @param int    $def Default value.
	 * @return int
	 */
	public static function get_int( $key, $def = 0 ) {
		return intval( self::get( $key, $def ) );
	}
}
