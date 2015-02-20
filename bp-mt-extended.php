<?php
/**
 * @package   BuddyPress Member Types extended
 * @author    imath https://twitter.com/imath
 * @license   GPL-2.0+
 * @link      http://imathi.eu
 * @copyright 2014 imath
 *
 * @wordpress-plugin
 * Plugin Name:       BuddyPress Member Types extended
 * Plugin URI:        http://imathi.eu/tag/bpmt-extended
 * Description:       Extends the BuddyPress member types, to add different UIs in Admin or front end
 * Version:           1.0.0-alpha
 * Author:            imath
 * Author URI:        http://imathi.eu
 * Text Domain:       bp-mt-extended
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages/
 * GitHub Plugin URI: https://github.com/imath/bp-mt-extended
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'BP_MT_Extended' ) ) :
/**
 * Main Plugin Class
 *
 * @since BuddyPress Member Types extended (1.0.0)
 */
class BP_MT_Extended {
	/**
	 * Instance of this class.
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Required BuddyPress version for the plugin.
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 *
	 * @var      string
	 */
	public static $required_bp_version = '2.2';

	/**
	 * BuddyPress config.
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 *
	 * @var      array
	 */
	public static $bp_config = array();

	/**
	 * Customizable parameters
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 *
	 * @var      array
	 */
	public $params = array();

	/**
	 * Initialize the plugin
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	private function __construct() {
		// First you will set your plugin's globals
		$this->setup_globals();
		// Then hook to BuddyPress actions & filters
		$this->setup_hooks();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function start() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Sets some globals for the plugin
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	private function setup_globals() {

		// Define a global that will hold the current version number
		$this->version       = '1.0.0-alpha';

		// Define a global to get the textdomain of your plugin.
		$this->domain        = 'bp-mt-extended';

		$this->file          = __FILE__;
		$this->basename      = plugin_basename( $this->file );
		$this->plugin_url    = plugin_dir_url( $this->file );

		// Define a global that we can use to construct url to the javascript scripts needed by the component
		$this->plugin_js     = trailingslashit( $this->plugin_url . 'js' );

		// Define a global that we can use to construct url to the css needed by the component
		$this->plugin_css    = trailingslashit( $this->plugin_url . 'css' );

		// Member type taxonomy
		$this->taxonomy = 'bp_member_type';

		// Set template hooks
		$this->set_params();

		// Set users screen to target
		$this->users_screen = 'users';
		$this->user_column  =  'manage_users_columns';

		// BuddyPress is activated network widely
		if ( bp_core_do_network_admin() && bp_is_network_activated() ) {
			$this->users_screen = 'users-network';
			$this->user_column  =  'wpmu_users_columns';
		}
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
			// Init Member types
			add_action( 'bp_init', array( $this, 'register_member_types' ) );

			// Enqueue the needed script and css files
			add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_front_scripts' ) );

			// loads the languages..
			add_action( 'bp_init', array( $this, 'load_textdomain' ), 5 );

			// Saves the plugin version in db
			add_action( bp_core_admin_hook(), array( $this, 'users_admin_menu' ) );

			// Ajax actions
			add_action( 'wp_ajax_bpmt_insert_term', array( $this, 'insert_term' ) );
			add_action( 'wp_ajax_bpmt_update_term', array( $this, 'update_term' ) );
			add_action( 'wp_ajax_bpmt_get_terms',   array( $this, 'get_terms'   ) );
			add_action( 'wp_ajax_bpmt_delete_term', array( $this, 'delete_term' ) );

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
	 * Display a new users menu to set member types
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function users_admin_menu() {
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			return false;
		}

		$this->screen_id = add_users_page(
			_x( 'Member Types', 'Member types admin page title', 'bp-mt-extended' ),
			_x( 'Member Types', 'Admin Users menu', 'bp-mt-extended' ),
			'manage_options',
			'bpmt-extended',
			array( $this, 'admin_page' )
		);

		add_action( "load-{$this->screen_id}", array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueues the needed scripts and css for the UI to set member types
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function enqueue_admin_scripts() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style  ( 'bpmt-extended-admin-style', $this->plugin_css . "bpmt-extended-admin$suffix.css", array( 'dashicons' ), $this->version );
		wp_enqueue_script ( 'bpmt-extended-admin-script', $this->plugin_js . "bpmt-extended-admin$suffix.js", array( 'wp-backbone' ), $this->version, true );
		wp_localize_script( 'bpmt-extended-admin-script', 'member_types_admin_vars', array(
			'nonce'               => wp_create_nonce( 'bpmt-extended-admin' ),
			'placeholder_default' => esc_html__( 'Name of your member type.', 'bp-mt-extended' ),
			'placeholder_saving'  => esc_html__( 'Saving the member type...', 'bp-mt-extended' ),
			'placeholder_success' => esc_html__( 'Success: type saved.', 'bp-mt-extended' ),
			'placeholder_error'   => esc_html__( 'Error: type not saved', 'bp-mt-extended' ),
			'alert_notdeleted'    => esc_html__( 'Error: type not deleted', 'bp-mt-extended' ),
			'current_edited_type' => esc_html__( 'Editing: %s', 'bp-mt-extended' ),
		) );
	}

	/**
	 * Output the Member types admin page
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function admin_page() {
		?>
		<div class="wrap">

			<?php screen_icon( 'users' ); ?>
			<h2><?php esc_html_e( 'Member Types', 'bp-mt-extended' ) ;?></h2>

			<p class="description bpmt-guide">
				<?php esc_html_e( 'Add your type in the field below and hit the return key to save it.', 'bp-mt-extended' ) ;?>
				<?php esc_html_e( 'To update a type, select it in the list, edit the name and hit the return key to save it.', 'bp-mt-extended' ) ;?>
			</p>

			<div class="bpmt-terms-admin">
				<div class="bpmt-terms-form"></div>
				<div class="bpmt-terms-list"></div>
			</div>

			<script id="tmpl-bpmt-term" type="text/html">
				<span class="bpmt-term-name">{{data.name}}</span> <span class="bpmt-term-actions"><a href="#" class="bpmt-term-edit" data-term_id="{{data.id}}" title="<?php esc_attr_e( 'Edit type', 'bp-mt-extended' );?>"></a> <a href="#" class="bpmt-term-delete" data-term_id="{{data.id}}" title="<?php esc_attr_e( 'Delete type', 'bp-mt-extended' );?>"></a></span>
			</script>

		</div>
		<?php
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
	 * Register the member types
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function register_member_types() {
		$terms = $this->term_wrapper_function( 'get_terms', array( $this->taxonomy, array( 'hide_empty' => false, 'fields' => 'id=>name' ) ) );

		if ( empty( $terms ) ) {
			return false;
		}

		/* see $this->update_terms_data() to understand why this is needed */
		$terms_data = bp_get_option( '_bp_mt_extended_terms_data', array() );

		foreach ( $terms as $term_id => $term_name ) {

			if ( empty( $terms_data[ $term_id ] ) ) {
				$terms_data[ $term->term_id ] = $term_name;
			}

			bp_register_member_type( $term_name, array(
				'labels' => array(
					'singular_name' => $terms_data[ $term_id ],
				),
			) );
		}
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
	 * Bulk set member types in Users Administration
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function bulk_actions( $return = false ) {
		$current_screen = get_current_screen();
		if ( empty( $current_screen->id ) || $this->users_screen != $current_screen->id ) {
			return;
		}

		// Bail if current user cannot manage options
		if ( ! bp_current_user_can( 'manage_options' ) ) {
			return;
		}

		$member_types = bp_get_member_types( array(), 'objects' );

		if ( empty( $member_types ) ) {
			return;
		}

		$output = '<label class="screen-reader-text" for="bpmt-set-type">' .  esc_html__( 'Change member type to&hellip;', 'bp-mt-extended' ) . '</label>';
		$output .= '<select name="bpmt-set-type" id="bpmt-set-type" style="display:inline-block; float:none;">';
		$output .= '<option value="">' .  esc_html__( 'Change member type to&hellip;', 'bp-mt-extended' ) . '</option>';

		foreach ( $member_types as $type_key => $type ) {
			$output .= sprintf( '<option value="%s">%s</option>', $type_key, esc_html( $type->labels['singular_name'] ) );
		}
		$output .= '</select>' . get_submit_button( __( 'Change', 'bp-mt-extended' ), 'secondary', 'bpmt-change-type', false );

		if ( ! empty( $return ) ) {
			return $output;
		} else {
			echo $output;
		}
	}

	/**
	 * Handle bulk actions
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function manage_bulk_actions() {
		$current_screen = get_current_screen();
		if ( empty( $current_screen->id ) || $this->users_screen != $current_screen->id ) {
			return;
		} else {
			// Globalize list table to prefetch member types
			global $wp_list_table;

			$suffix = SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script ( 'bpmt-extended-users-admin-script', $this->plugin_js . "bpmt-extended-users-admin$suffix.js", array( 'bp-jquery-query' ), $this->version, true );
			$string_vars = array(
				'message_plural'   => esc_html__( '%s members had their type changed.', 'bp-mt-extended' ),
				'message_singular' => esc_html__( '%s member had his type changed.', 'bp-mt-extended' ),
			);

			if ( is_multisite() ) {
				$string_vars['bulk_output'] = $this->bulk_actions( true );
			}

			wp_localize_script( 'bpmt-extended-users-admin-script', 'member_types_users_admin_vars', $string_vars );
		}

		// Bail if current user cannot manage options
		if ( ! bp_current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_REQUEST['bpmt-change-type'] ) ) {
			return;
		}

		if ( is_multisite() ) {
			$users = ! empty( $_REQUEST['allusers'] ) ? $_REQUEST['allusers'] : false;
		} else {
			$users = ! empty( $_REQUEST['users'] ) ? $_REQUEST['users'] : false;
		}

		// Bail if this isn't a bbPress action
		if ( empty( $_REQUEST['bpmt-set-type'] ) || empty( $users ) ) {
			return;
		}

		$member_type = sanitize_key( $_REQUEST['bpmt-set-type'] );

		// Check member type exists
		if ( ! bp_get_member_type_object( $member_type ) ) {
			return;
		}

		$updated = 0;

		// Loop through user ids
		foreach ( (array) $users as $user_id ) {
			$user_id = (int) $user_id;

			// Set the new forums role
			if ( bp_set_member_type( $user_id, $member_type ) ) {
				$updated += 1;
			}
		}

		$redirect = add_query_arg( array( 'updated' => $updated ), bp_get_admin_url( 'users.php' ) );
		wp_safe_redirect( $redirect );
		exit();
	}

	/**
	 * Prefetch the member types in the Administration users list
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function admin_prefetch_types() {
		$current_screen = get_current_screen();
		if ( empty( $current_screen->id ) || $this->users_screen != $current_screen->id ) {
			return;
		}

		global $wp_list_table;

		if ( ! empty( $wp_list_table->items ) ) {
			// create an empty BP User Query
			$users = new BP_User_Query;

			$users->user_ids = wp_list_pluck( $wp_list_table->items, 'ID' );

			// Cache the member types
			bp_members_prefetch_member_type( $users );
		}
	}

	/**
	 * Adds a column to admin user listing to show drive usage
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function users_admin_row_columns( $columns = array() ) {
		$nav = esc_html__( 'Member types', 'bp-mt-extended' );

		if ( ! empty( $this->params['nav_label'] ) ) {
			$nav = esc_html( $this->params['nav_label'] );
		}

		$columns['bmpt_type'] = $nav;

		return $columns;
	}

	/**
	 * Displays the row data for our new column
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function users_admin_row_data( $retval = '', $column_name = '', $user_id = 0 ) {

		if ( 'bmpt_type' === $column_name && ! empty( $user_id ) ) {
			$member_type = bp_get_member_type( $user_id, false );
			$retval      = $this->get_member_type_singular_name( $member_type );
		}

		// Pass retval through
		return $retval;
	}

	/**
	 * Wrapper function to force the use of taxonomy functions in the root blog context
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	private function term_wrapper_function( $function = '', $args = array() ) {
		if ( ! function_exists( $function ) ) {
			return false;
		}

		if ( ! bp_is_root_blog() ) {
			switch_to_blog( bp_get_root_blog_id() );
		}

		$retval = call_user_func_array( $function, $args );

		restore_current_blog();

		return $retval;
	}

	/**
	 * Member types are using terms in a specific way.
	 *
	 * labels are in a global, and the term_name is used as a kind of unique key
	 * so we can't use it as every member types functions are using the name attribute
	 * instead of the slug one. This option is there to set a singular name
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 *
	 * @param  int    $term_id the term_id
	 * @param  string $name    the member type singular name
	 * @return bool
	 */
	public function update_terms_data( $term_id = 0, $name = '' ) {
		if ( empty( $term_id ) ) {
			return false;
		}

		$terms_data = bp_get_option( '_bp_mt_extended_terms_data', array() );

		if ( ( empty( $terms_data[ $term_id ] ) && ! empty( $name ) ) || ( ! empty( $terms_data[ $term_id ] ) && ! empty( $name ) ) ) {
			$terms_data[ $term_id ] = $name;
		} else if ( ! empty( $terms_data[ $term_id ] ) && empty( $name ) ) {
			unset( $terms_data[ $term_id ] );
		}

		bp_update_option( '_bp_mt_extended_terms_data', $terms_data );

		return true;
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

	/**
	 * AJAX populate the existing terms in Member types admin
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function get_terms() {
		check_ajax_referer( 'bpmt-extended-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( ! $this->term_wrapper_function( 'taxonomy_exists', array( $this->taxonomy ) ) ) {
			wp_send_json_error();
		}

		$terms = $this->term_wrapper_function( 'get_terms', array( $this->taxonomy, array( 'hide_empty' => false ) ) );
		$terms = array_map( array( $this, 'prepare_for_js' ), array_values( $terms ) );
		$terms = array_filter( $terms );

		wp_send_json_success( $terms );
	}

	/**
	 * AJAX create a new term from Member types admin
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function insert_term() {
		$bp = buddypress();

		if ( ! isset( $_POST['bpmt_name'] ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'bpmt-extended-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( ! $this->term_wrapper_function( 'taxonomy_exists', array( $this->taxonomy ) ) ) {
			wp_send_json_error();
		}

		// To use as the singular name label
		$name = esc_html( $_POST['bpmt_name'] );

		// To use as a term identifier
		$sanitized_term = sanitize_key( $name );

		// Check if not already existing
		if ( isset( $bp->members->types[ $sanitized_term ] ) ) {
			wp_send_json_error();
		}

		$inserted = $this->term_wrapper_function( 'wp_insert_term', array( $sanitized_term, $this->taxonomy ) );

		if ( is_wp_error( $inserted ) || empty( $inserted['term_id'] )  ) {
			wp_send_json_error();
		}

		if ( $this->update_terms_data( $inserted['term_id'], $name ) ) {
			$term = $this->prepare_for_js( $this->term_wrapper_function( 'get_term', array( $inserted['term_id'], $this->taxonomy ) ) );
		}

		if ( empty( $term ) ) {
			wp_send_json_error();
		}

		wp_send_json_success( $term );
	}

	/**
	 * AJAX update the singular name from Member types admin
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function update_term() {
		if ( ! isset( $_POST['bpmt_id'] ) || empty( $_POST['bpmt_name'] ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'bpmt-extended-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( ! $this->term_wrapper_function( 'taxonomy_exists', array( $this->taxonomy ) ) ) {
			wp_send_json_error();
		}

		$term_id   = intval( $_POST['bpmt_id'] );
		$term_name = esc_html( $_POST['bpmt_name'] );

		if ( ! $this->update_terms_data( $term_id, $term_name ) ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * AJAX delete a term from Member types admin
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function delete_term() {
		if ( ! isset( $_POST['bpmt_id'] ) ) {
			wp_send_json_error();
		}

		check_ajax_referer( 'bpmt-extended-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		if ( ! $this->term_wrapper_function( 'taxonomy_exists', array( $this->taxonomy ) ) ) {
			wp_send_json_error();
		}

		$term_id = intval( $_POST['bpmt_id'] );

		$deleted = $this->term_wrapper_function( 'wp_delete_term', array( $term_id, $this->taxonomy ) );

		if ( is_wp_error( $deleted ) || empty( $deleted ) ) {
			wp_send_json_error();
		}

		if ( ! $this->update_terms_data( $term_id ) ) {
			wp_send_json_error();
		}

		wp_send_json_success();
	}

	/**
	 * Set the template hooks where to inject the member type
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public function set_params() {
		$this->params = bp_parse_args( array(), array(
			'hooks' => array(
				'directory_hook'      => 'bp_directory_members_item',
				'member_hook'         => 'bp_profile_header_meta',
			),
			'nav_label' => '',

		), 'bpmt_template_hooks' );
	}

	/** Utilities *****************************************************************************/

	/**
	 * Checks BuddyPress version
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public static function version_check() {
		// taking no risk
		if ( ! defined( 'BP_VERSION' ) )
			return false;

		return version_compare( BP_VERSION, self::$required_bp_version, '>=' );
	}

	/**
	 * Checks if your plugin's config is similar to BuddyPress
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public static function config_check() {
		/**
		 * blog_status    : true if your plugin is activated on the same blog
		 * network_active : true when your plugin is activated on the network
		 * network_status : BuddyPress & your plugin share the same network status
		 */
		self::$bp_config = array(
			'blog_status'    => false,
			'network_active' => false,
			'network_status' => true
		);

		if ( get_current_blog_id() == bp_get_root_blog_id() ) {
			self::$bp_config['blog_status'] = true;
		}

		$network_plugins = get_site_option( 'active_sitewide_plugins', array() );

		// No Network plugins
		if ( empty( $network_plugins ) )
			return self::$bp_config;

		$plugin_basename = plugin_basename( __FILE__ );

		// Looking for BuddyPress and your plugin
		$check = array( buddypress()->basename, $plugin_basename );

		// Are they active on the network ?
		$network_active = array_diff( $check, array_keys( $network_plugins ) );

		// If result is 1, your plugin is network activated
		// and not BuddyPress or vice & versa. Config is not ok
		if ( count( $network_active ) == 1 )
			self::$bp_config['network_status'] = false;

		// We need to know if the plugin is network activated to choose the right
		// notice ( admin or network_admin ) to display the warning message.
		self::$bp_config['network_active'] = isset( $network_plugins[ $plugin_basename ] );

		return self::$bp_config;
	}

	/**
	 * Bail if BuddyPress config is different than this plugin
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 */
	public static function bail() {
		$retval = false;

		$config = self::config_check();

		if ( ! self::version_check() || ! $config['blog_status'] || ! $config['network_status'] )
			$retval = true;

		return $retval;
	}

	/**
	 * Loads the translation files
	 *
	 * @package BuddyPress Member Types extended
	 *
	 * @since BuddyPress Member Types extended (1.0.0)
	 *
	 * @uses get_locale() to get the language of WordPress config
	 * @uses load_texdomain() to load the translation if any is available for the language
	 * @uses load_plugin_textdomain() to load the translation if any is available for the language
	 */
	public function load_textdomain() {
		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to a bp-mt-extended subfolder in WP LANG DIR
		$mofile_global = WP_LANG_DIR . '/bp-mt-extended/' . $mofile;

		// Look in global /wp-content/languages/bp-mt-extended folder
		if ( ! load_textdomain( $this->domain, $mofile_global ) ) {

			// Look in local /wp-content/plugins/bp-mt-extended/languages/ folder
			// or /wp-content/languages/plugins/
			load_plugin_textdomain( $this->domain, false, basename( plugin_dir_path( $this->file ) ) . '/languages' );
		}
	}
}

endif;

// BuddyPress is loaded and initialized, let's start !
function bpmt_extended() {
	return BP_MT_Extended::start();
}
add_action( 'bp_include', 'bpmt_extended' );
