<?php
/**
 * Plugin Globals.
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

// Legacy
function types_de_membre_directory_tabs() {
	printf(
		'<li id="members-types" class="no-ajax">
			<a href="#">%s</a>
		</li>',
		esc_html__( 'Types de membre', 'types-de-membre' )
	);
}
add_action( 'bp_members_directory_member_types', 'types_de_membre_directory_tabs' );

// Nouveau
function types_de_membre_directory_nav_items( $nav_items = array() ) {
	if ( isset( $nav_items['types'] ) ) {
		$nav_items['types']['li_class'] = array( 'no-ajax', 'last' );
	}

	return $nav_items;
}
add_filter( 'bp_nouveau_get_members_directory_nav_items', 'types_de_membre_directory_nav_items', 10, 1 );
