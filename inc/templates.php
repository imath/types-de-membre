<?php
/**
 * Plugin Template Functions.
 *
 * @package Types de membre
 * @subpackage \inc\templates
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects Plugin's templates dir into the BP Templates dir stack, when on the members directory.
 *
 * @since 2.0.0
 *
 * @param  array $template_stack The list of available locations to get BuddyPress templates.
 * @return array                 The list of available locations to get BuddyPress templates.
 */
function types_de_membre_template_stack( $template_stack = array() ) {
	if ( ! bp_is_members_directory() ) {
		return $template_stack;
	}

	$template_pack_dir       = trailingslashit( bp_get_theme_compat_dir() ) . 'buddypress';
	$template_pack_dir_index = array_search( $template_pack_dir, $template_stack );

	// Only override BP Template pack.
	array_splice(
		$template_stack,
		$template_pack_dir_index,
		1,
		array(
			trailingslashit( types_de_membre()->tmpl ) . 'buddypress',
			$template_pack_dir,
		)
	);

	return $template_stack;
}
add_filter( 'bp_get_template_stack', 'types_de_membre_template_stack', 10, 1 );

/**
 * Restore the Plugin's template dir into the stack.
 *
 * This is needed to make sure the plugin's common/nav/directory-nav.php template
 * overrides the BP Nouveau one.
 *
 * @since 2.0.0
 */
function types_de_membre_locate_template_part() {
	if ( 'nouveau' === bp_get_theme_compat_id() ) {
		add_filter( 'bp_get_template_stack', 'types_de_membre_template_stack', 10, 1 );
		remove_action( 'bp_locate_template', 'types_de_membre_locate_template_part', 10, 0 );
	}
}

/**
 * Temporarly removes the Plugin's template dir from the stack.
 *
 * This is needed to make sure the members/index.php provided to be compatible
 * with BP Legacy is not used when the active template pack is BP Nouveau.
 *
 * @since 2.0.0
 */
function types_de_membre_get_template_part() {
	if ( 'nouveau' === bp_get_theme_compat_id() ) {
		remove_filter( 'bp_get_template_stack', 'types_de_membre_template_stack', 10, 1 );
		add_action( 'bp_locate_template', 'types_de_membre_locate_template_part', 10, 0 );
	}
}
add_action( 'get_template_part_members/index', 'types_de_membre_get_template_part', 10, 0 );


/**
 * Outputs the Member Types navigation.
 *
 * @since 2.0.0
 *
 * @param string $current_member_type The current member type to filter the loop with.
 * @return string HTML Output.
 */
function types_de_membre_get_directory_nav_items( $current_member_type = '' ) {
	$member_types = bp_get_member_types( array(), 'objects' );

	if ( empty( $member_types ) ) {
		return;
	}

	/**
	 * Filter here to edit the Member Types navigation name.
	 *
	 * @since 2.0.0
	 *
	 * @param string $value the Member Types navigation name.
	 */
	$nav = apply_filters(
		'types_de_membre_get_directory_nav_name',
		__( 'Types de membre', 'types-de-membre' )
	);

	$classes = array();
	if ( 'legacy' === bp_get_theme_compat_id() ) {
		$classes = array( 'no-ajax' );
	}

	$types_list = '';
	foreach ( $member_types as $member_type ) {
		if ( empty( $member_type->has_directory ) ) {
			continue;
		}

		$member_type_text = $member_type->labels['name'];
		if ( isset( $member_type->labels['singular_name'] ) && $member_type->labels['singular_name'] ) {
			$member_type_text = $member_type->labels['singular_name'];
		}

		$class_link = '';
		if ( $classes ) {
			$class_link = sprintf( ' class="%s"', reset( $classes ) );
		}

		if ( $member_type->name === $current_member_type ) {
			$classes = array_merge( $classes, array( 'selected', 'current' ) );
		} else {
			$classes = array_diff( $classes, array( 'selected', 'current' ) );
		}

		$class_attribute = '';
		if ( $classes ) {
			$class_attribute = sprintf( ' class="%s"', implode( ' ', array_map( 'sanitize_html_class', $classes ) ) );
		}

		$types_list .= sprintf(
			'<li id="member-type-%1$s"%2$s>
				<a href="%3$s"%4$s>%5$s</a>
			</li>',
			esc_attr( $member_type->name ),
			$class_attribute,
			esc_url( bp_get_member_type_directory_permalink( $member_type->name ) ),
			$class_link,
			esc_html( $member_type_text )
		);
	}

	if ( ! $types_list ) {
		return;
	}

	$output = sprintf(
		'<li id="members-types" class="no-ajax select-wrap">
			<a id="member-types-toggler" class="no-ajax" href="#open" >%1$s <span class="select-arrow no-ajax" aria-hidden="true"></span></a>
			<ul class="member-types">
				%2$s
			</ul>
		</li>',
		esc_html( $nav ),
		$types_list
	);

	/**
	 * Filter here to edit the nav items output.
	 *
	 * @since 2.0.0
	 *
	 * @param string $output The nav items output.
	 * @param string $current_member_type The current member type.
	 */
	return apply_filters( 'types_de_membre_get_directory_nav_items', $output, $current_member_type );
}

/**
 * Outputs the Member Types navigation.
 *
 * @since 2.0.0
 */
function types_de_membre_directory_nav() {
	if ( ! bp_get_member_types() ) {
		return;
	}

	wp_enqueue_style( 'types-de-membre' );
	wp_enqueue_script( 'types-de-membre' );

	// Get current member type.
	$current_member_type = bp_get_current_member_type();
	$nav_items           = types_de_membre_get_directory_nav_items( $current_member_type );

	if ( ! $nav_items ) {
		return;
	}

	// Set the active member type if needed.
	$active_descendant = 'member-types-toggler';
	if ( $current_member_type ) {
		$active_descendant = 'member-type-' . $current_member_type;
	}
	?>
	<ul class="component-navigation types-nav" role="listbox" aria-activedescendant="<?php echo esc_attr( $active_descendant ); ?>">
		<?php echo types_de_membre_get_directory_nav_items( $current_member_type ); ?>
	</ul>
	<?php
}
