<?php
/**
 * Types de membre: description of the plugin.
 *
 * @package   Types de membre
 * @author    imath
 * @license   GPL-2.0+
 * @link      https://imathi.eu
 *
 * @buddypress-plugin
 * Plugin Name:       Types de membre
 * Plugin URI:        https://github.com/imath/types-de-membre
 * Description:       Description de l'extension.
 * Version:           2.0.0-alpha
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

		/* Load Admin.
		if ( is_admin() ) {
			require $inc_path . 'admin.php';
		}*/
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
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Sets the key hooks to add an action or a filter to
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	private function setup_hooks() {

		if ( ! self::bail() ) {

			// Add the directory tabs
			add_action( 'bp_members_directory_member_types', array( $this, 'directory_tabs' ) );

			// Output the member types in members directory items
			// Or in the member's header
			foreach ( $this->params['hooks'] as $action => $hook ) {
				add_action( $hook, array( $this, $action ) );
			}

			// Filter the request
			add_filter( 'bp_before_has_members_parse_args', array( $this, 'has_members_type_arg' ), 10, 1 );

			// Clean count cache when a term is updated
			add_action( 'edited_term_taxonomy', array( $this, 'clean_term_count_cache' ), 10, 2 );

			// Administration
			add_action( 'restrict_manage_users',      array( $this, 'bulk_actions'            )        );
			add_action( 'load-users.php',             array( $this, 'manage_bulk_actions'     )        );
			add_action( 'admin_head-users.php',       array( $this, 'admin_prefetch_types'    )        );
			add_filter( $this->user_column,           array( $this, 'users_admin_row_columns' ), 10, 1 );
			add_filter( 'manage_users_custom_column', array( $this, 'users_admin_row_data'    ), 10, 3 );

		} else {
			// Display a warning message in network admin or admin
			add_action( self::$bp_config['network_active'] ? 'network_admin_notices' : 'admin_notices', array( $this, 'warning' ) );
		}

	}

	/**
	 * Display a warning message to admin
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function warning() {
		$warnings = array();

		if( ! self::version_check() ) {
			$warnings[] = sprintf( __( 'BuddyPress Member Types extended requires at least version %s of BuddyPress.', 'bp-mt-extended' ), self::$required_bp_version );
		}

		if ( ! empty( self::$bp_config ) ) {
			$config = self::$bp_config;
		} else {
			$config = self::config_check();
		}

		if ( ! bp_core_do_network_admin() && ! $config['blog_status'] ) {
			$warnings[] = __( 'BuddyPress Member Types extended requires to be activated on the blog where BuddyPress is activated.', 'bp-mt-extended' );
		}

		if ( bp_core_do_network_admin() && ! $config['network_status'] ) {
			$warnings[] = __( 'BuddyPress Member Types extended and BuddyPress need to share the same network configuration.', 'bp-mt-extended' );
		}

		if ( ! empty( $warnings ) ) :
		?>
		<div id="message" class="error">
			<?php foreach ( $warnings as $warning ) : ?>
				<p><?php echo esc_html( $warning ) ; ?></p>
			<?php endforeach ; ?>
		</div>
		<?php
		endif;
	}

	/**
	 * Enqueue front end scripts/css if viewing the members directory
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function enqueue_front_scripts() {
		if ( ! bp_is_members_directory() ) {
			return;
		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style  ( 'bpmt-extended-front-style', $this->plugin_css . "bpmt-extended-front$suffix.css", array(), $this->version );
		wp_enqueue_script ( 'bpmt-extended-front-script', $this->plugin_js . "bpmt-extended-front$suffix.js", array( 'bp-jquery-cookie' ), $this->version, true );
	}

	/**
	 * Display the number of members by types.
	 *
	 * This works fine if order is alphabetical, else it would
	 * require to uncount the ! last_active members...
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function count_member_types( $member_type = '' ) {
		global $wpdb;
		$member_types = bp_get_member_types();

		if ( empty( $member_type ) || empty( $member_types[ $member_type ] ) ) {
			return false;
		}

		$count_types = wp_cache_get( 'bpmt_count_member_types', 'bpmt_extended' );

		if ( ! $count_types ) {
			if ( ! bp_is_root_blog() ) {
				switch_to_blog( bp_get_root_blog_id() );
			}

			$sql = array(
				'select' => "SELECT t.slug, tt.count FROM {$wpdb->term_taxonomy} tt LEFT JOIN {$wpdb->terms} t",
				'on'     => 'ON tt.term_id = t.term_id',
				'where'  => $wpdb->prepare( 'WHERE tt.taxonomy = %s', $this->taxonomy ),
			);

			$count_types = $wpdb->get_results( join( ' ', $sql ) );
			wp_cache_set( 'bpmt_count_member_types', $count_types, 'bpmt_extended' );

			restore_current_blog();
		}

		$type_count = wp_filter_object_list( $count_types, array( 'slug' => $member_type ), 'and', 'count' );
		$type_count = array_values( $type_count );

		if ( empty( $type_count ) ) {
			return 0;
		}

		return (int) $type_count[0];
	}

	/**
	 * Clean term count cache
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function clean_term_count_cache( $term = 0, $taxonomy = null ) {
		if ( empty( $term ) || empty( $taxonomy->name ) || 'bp_member_type' != $taxonomy->name )  {
			return;
		}

		wp_cache_delete( 'bpmt_count_member_types', 'bpmt_extended' );
	}

	/**
	 * Display tabs used to filter the members directory by types.
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	function directory_tabs() {
		$member_types = bp_get_member_types( array(), 'objects' );

		if ( empty( $member_types ) ) {
			return;
		}

		$nav = esc_html__( 'Member types', 'bp-mt-extended' );

		if ( ! empty( $this->params['nav_label'] ) ) {
			$nav = esc_html( $this->params['nav_label'] );
		}
		?>

		<li id="members-types">
			<a href="#" class="no-ajax"><?php echo $nav; ?> <span class="no-ajax">&or;</span></a>
			<ul class="member-types">

			<?php foreach ( $member_types as $member_type ) :
				$term_count = $this->count_member_types( $member_type->name );

				if ( empty( $term_count ) ) {
					continue;
				}
			?>

				<li id="members-<?php echo esc_attr( $member_type->name ) ;?>">
					<a href="<?php bp_members_directory_permalink(); ?>"><?php printf( '%s <span>%d</span>', $member_type->labels['singular_name'], $term_count ); ?></a>
				</li>

			<?php endforeach; ?>

			</ul>
		</li>

		<?php
	}

	/**
	 * Get the singular name out of of member type's name or array of names
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	function get_member_type_singular_name( $name = '' ) {
		if ( empty( $name ) ) {
			return false;
		}

		$singular_name = '';

		if ( is_array( $name ) ) {
			$member_types_objects = bp_get_member_types( array(), 'objects' );

			$singular_name_array = array();
			foreach ( $name as $name_type ) {
				if ( empty( $member_types_objects[ $name_type ]->labels['singular_name'] ) ) {
					continue;
				}

				$singular_name_array[] = $member_types_objects[ $name_type ]->labels['singular_name'];
			}

			$singular_name = join( ', ', $singular_name_array );
		} else {
			$member_type_object = bp_get_member_type_object( $name );

			if ( ! empty( $member_type_object->labels['singular_name'] ) )  {
				$singular_name = $member_type_object->labels['singular_name'];
			}
		}

		return $singular_name;
	}

	/**
	 * Output the member type for each members of the loop
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function directory_hook() {
		$member_type   = bp_get_member_type( bp_get_member_user_id(), false );
		$singular_name = $this->get_member_type_singular_name( $member_type );

		echo apply_filters( 'bpmt_directory_hook_output', esc_html( $singular_name ), $member_type );
	}

	/**
	 * Output the member's type in the profile header of the member
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function member_hook() {
		$member_type = bp_get_member_type( bp_displayed_user_id() );

		if ( empty( $member_type ) ) {
			return;
		}

		$member_type_object = bp_get_member_type_object( $member_type );

		echo apply_filters( 'bpmt_member_hook_output',
			'<p class="member_type">' . esc_html( $member_type_object->labels['singular_name'] ) . '</p>',
			$member_type_object->labels['singular_name'],
			$member_type_object
		);
	}

	/**
	 * Use the members scope to filter the members loop by member type
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 * // Filter the request
	 * add_filter( 'bp_before_has_members_parse_args', array( $this, 'has_members_type_arg' ), 10, 1 );
	 */
	public function has_members_type_arg( $args = array() ) {
		// Get member types to check scope
		$member_types = bp_get_member_types( array(), 'objects' );

		// Set the member type arg if scope match one of the registered member type
		if ( ! empty( $args['scope'] ) && ! empty( $member_types[ $args['scope'] ] ) ) {
			$args['member_type'] = $args['scope'];
		}

		return $args;
	}

	/**
	 * Prepare the response for the js context
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	private function prepare_for_js( $term = null ) {
		if ( ! is_object( $term ) ) {
			return false;
		}

		$terms_data = bp_get_option( '_bp_mt_extended_terms_data', array() );

		if ( empty( $terms_data[ $term->term_id ] ) ) {
			$terms_data = array( $term->term_id => $term->name );
		}

		$response = array(
			'id'    => intval( $term->term_id ),
			'name'  => $terms_data[ $term->term_id ],
			'slug'  => $term->slug,
			'count' => intval( $term->count ),
		);

		return $response;
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
