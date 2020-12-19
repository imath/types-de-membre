<?php
/**
 * Types de membre: Adds a dropdown control to filter the BuddyPress Members directory by types.
 *
 * @package   Types de membre
 * @author    imath
 * @license   GPL-2.0+
 * @link      https://imathi.eu
 *
 * @buddypress-plugin
 * Plugin Name:       Types de membre
 * Plugin URI:        https://github.com/imath/types-de-membre
 * Description:       Ajoute un liste déroulante pour filtrer le répertoire des membres par type.
 * Version:           2.0.0
 * Author:            imath
 * Author URI:        https://imathi.eu
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 * Text Domain:       types-de-membre
 * GitHub Plugin URI: https://github.com/imath/types-de-membre
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class
 *
 * @since 2.0.0
 */
final class Types_De_Membre {
	/**
	 * Plugin instance.
	 *
	 * @since 2.0.0
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Plugin constructor.
	 *
	 * @since 2.0.0
	 */
	private function __construct() {
		// Includes path.
		$inc_path = plugin_dir_path( __FILE__ ) . 'inc/';

		// Load Globals & Functions.
		require $inc_path . 'globals.php';
		require $inc_path . 'functions.php';
		require $inc_path . 'templates.php';

		if ( is_admin() ) {
			require $inc_path . 'admin.php';
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 2.0.0
	 *
	 * @return object The main instance of the plugin.
	 */
	public static function start() {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

/**
 * Starts the plugin.
 *
 * @since 2.0.0
 */
function types_de_membre() {
	return Types_De_Membre::start();
}
add_action( 'bp_loaded', 'types_de_membre', 3 );
