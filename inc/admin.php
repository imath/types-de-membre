<?php
/**
 * Plugin Admin Functions.
 *
 * @package Types de membre
 * @subpackage \inc\admin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the Plugin's DB version.
 *
 * @since 2.0.0
 *
 * @return string The Plugin's DB version.
 */
function types_de_membre_get_db_version() {
	return bp_get_option( '_types_de_membre_version', '' );
}

/**
 * Returns whether it's a first install or not.
 *
 * @since 2.0.0
 *
 * @return boolean True if it's a first install or not. False otherwise.
 */
function types_de_membre_is_install() {
	$db_version = (bool) types_de_membre_get_db_version();

	return ! $db_version;
}

/**
 * Returns whether it's a first install or not.
 *
 * @since 2.0.0
 *
 * @return boolean True if it's a first install or not. False otherwise.
 */
function types_de_membre_is_update() {
	$db_version = types_de_membre_get_db_version();
	$version    = types_de_membre_version();

	return version_compare( $db_version, $version, '<' );
}

/**
 * Updates the plugin.
 *
 * @since 2.0.0
 */
function types_de_membre_update_plugin() {
	$version            = types_de_membre_version();
	$needs_version_bump = false;

	if ( types_de_membre_is_install() ) {
		$bp_mte_types = bp_get_option( '_bp_mt_extended_terms_data', array() );
		$bp_file      = trailingslashit( buddypress()->plugin_dir ) . 'bp-core/admin/bp-core-admin-types.php';

		// The user is upgrading BP MT Extended.
		if ( $bp_mte_types && file_exists( $bp_file ) ) {
			require_once $bp_file;

			$member_types = bp_get_terms(
				array(
					'taxonomy' => bp_get_member_type_tax_name(),
					'include'  => array_keys( $bp_mte_types ),
					'fields'   => 'id=>slug',
				)
			);

			foreach ( $member_types as $term_id => $slug ) {
				if ( ! isset( $bp_mte_types[ $term_id ] ) ) {
					continue;
				}

				bp_core_admin_update_type(
					array(
						'taxonomy'               => bp_get_member_type_tax_name(),
						'type_term_id'           => $term_id,
						'bp_type_name'           => $bp_mte_types[ $term_id ],
						'bp_type_singular_name'  => $bp_mte_types[ $term_id ],
						'bp_type_has_directory'  => true,
						'bp_type_directory_slug' => $slug,
						'bp_type_show_in_list'   => true,
					)
				);
			}

			$needs_version_bump = bp_delete_option( '_bp_mt_extended_terms_data' );

			// Just in case something went wrong keep the original values for a time.
			set_site_transient( 'bp_mt_extended_terms_data', $bp_mte_types, DAY_IN_SECONDS );
		} else {
			$needs_version_bump = true;
		}
	} elseif ( types_de_membre_is_update() ) {
		$needs_version_bump = true;
	}

	if ( $needs_version_bump ) {
		bp_update_option( '_types_de_membre_version', $version );
	}
}
add_action( 'bp_admin_init', 'types_de_membre_update_plugin', 1001 );
