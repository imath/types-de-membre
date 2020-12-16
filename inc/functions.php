<?php
/**
 * Plugin Generic Functions.
 *
 * @package Types de membre
 * @subpackage \inc\functions
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads translations.
 *
 * @since 2.0.0
 */
function types_de_membre_translate() {
	load_plugin_textdomain( 'types-de-membre', false, trailingslashit( basename( types_de_membre()->dir ) ) . 'languages' );
}
add_action( 'bp_loaded', 'types_de_membre_translate', 5 );

/**
 * Returns the plugin's current version.
 *
 * @since 2.0.0
 */
function types_de_membre_version() {
	return types_de_membre()->version;
}

/**
 * Returns the plugin's CSS URL.
 *
 * @since 2.0.0
 */
function types_de_membre_css_url() {
	return trailingslashit( types_de_membre()->url ) . 'css';
}

/**
 * Returns the plugin's JS URL.
 *
 * @since 2.0.0
 */
function types_de_membre_js_url() {
	return trailingslashit( types_de_membre()->url ) . 'js';
}

/**
 * Registers the plugin's style.
 *
 * @since 2.0.0
 */
function types_de_membre_register_style() {
	wp_register_style(
		'types-de-membre',
		sprintf( '%1$s/style%2$s.css', types_de_membre_css_url(), bp_core_get_minified_asset_suffix() ),
		array(),
		types_de_membre_version()
	);
}
add_action( 'bp_enqueue_scripts', 'types_de_membre_register_style', 1 );

/**
 * Registers the plugin's script.
 *
 * @since 2.0.0
 */
function types_de_membre_register_script() {
	wp_register_script(
		'types-de-membre',
		sprintf( '%1$s/script%2$s.js', types_de_membre_js_url(), bp_core_get_minified_asset_suffix() ),
		array(),
		types_de_membre_version(),
		true
	);
}
add_action( 'bp_enqueue_scripts', 'types_de_membre_register_script', 2 );
