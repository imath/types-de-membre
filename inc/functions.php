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
		sprintf( '%s/style.css', types_de_membre_css_url() ),
		array( 'dashicons' ),
		types_de_membre_version()
	);

	if ( 'twentytwentyone' === get_template() ) {
		wp_add_inline_style(
			'types-de-membre',
			'#buddypress .dir-navs ul.types-nav li,
			#buddypress .item-list-tabs ul.types-nav li {
				border: 1px solid var(--global--color-primary);
				background-color: var(--global--color-white-90);
				color: var(--form--color-text);
			}

			#buddypress .dir-navs ul.types-nav li > a:hover span.select-arrow:before,
			#buddypress .dir-navs ul.types-nav li > a:focus span.select-arrow:before,
			#buddypress .item-list-tabs ul.types-nav li > a:focus span.select-arrow:before,
			#buddypress .item-list-tabs ul.types-nav li > a:focus {
				color: var(--button--color-text-hover);
			}

			#buddypress .dir-navs ul.types-nav li ul.member-types li,
			#buddypress .item-list-tabs ul.types-nav li ul.member-types li {
				background-color: var(--global--color-white-90);
				border-color: var(--global--color-white-90);
			}'
		);
	}
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
		sprintf( '%s/script.js', types_de_membre_js_url() ),
		array(),
		types_de_membre_version(),
		true
	);
}
add_action( 'bp_enqueue_scripts', 'types_de_membre_register_script', 2 );
