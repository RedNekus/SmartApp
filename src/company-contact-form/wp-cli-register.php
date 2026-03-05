<?php
/**
 * Explicit WP-CLI command registration for Company Contact Form
 *
 * This file is required by wp-cli.yml to register commands explicitly,
 * bypassing WP-CLI's auto-discovery which doesn't work reliably with namespaced classes.
 */

if ( ! class_exists( 'WP_CLI' ) || ! class_exists( 'CCF\\CLI' ) ) {
	return;
}

// Register each subcommand explicitly as a closure
WP_CLI::add_command(
	'company-contact verify-hubspot',
	function ( $args, $assoc_args ) {
		return CCF\CLI::verify_hubspot( $args, $assoc_args );
	},
	array(
		'shortdesc' => 'Verify HubSpot connection (test API credentials)',
	)
);

WP_CLI::add_command(
	'company-contact submissions',
	function ( $args, $assoc_args ) {
		return CCF\CLI::submissions( $args, $assoc_args );
	},
	array(
		'shortdesc' => 'List recent form submissions',
	)
);

WP_CLI::add_command(
	'company-contact logs',
	function ( $args, $assoc_args ) {
		return CCF\CLI::logs( $args, $assoc_args );
	},
	array(
		'shortdesc' => 'View logger entries',
	)
);

WP_CLI::add_command(
	'company-contact rotate-logs',
	function ( $args, $assoc_args ) {
		return CCF\CLI::rotate_logs( $args, $assoc_args );
	},
	array(
		'shortdesc' => 'Clear old logs (manual rotation)',
	)
);
