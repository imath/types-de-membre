<?php
/**
 * Plugin Globals.
 *
 * @package Types de membre
 * @subpackage \inc\globals
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register plugin globals.
 *
 * @since 2.0.0
 */
function types_de_membre_globals() {
	$plugin = types_de_membre();

	$plugin->version = '2.0.0';

	// Dir.
	$plugin->dir = plugin_dir_path( dirname( __FILE__ ) );

	// Templates dir.
	$plugin->tmpl = trailingslashit( $plugin->dir ) . 'templates';

	// Languages path.
	$plugin->lang_path = trailingslashit( dirname( dirname( __FILE__ ) ) ) . 'languages';

	// URL.
	$plugin->url = plugin_dir_url( dirname( __FILE__ ) );
}
add_action( 'bp_loaded', 'types_de_membre_globals', 4 );
