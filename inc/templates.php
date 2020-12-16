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
 * Outputs the Member Types navigation.
 *
 * @since 2.0.0
 */
function types_de_membre_directory_nav_items( $current_member_type = '' ) {
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
	?>
	<li id="members-types" class="no-ajax select-wrap">
		<a id="member-types-toggler" class="no-ajax" href="#open" ><?php echo esc_html( $nav ); ?> <span class="select-arrow" aria-hidden="true"></span></a>
		<ul class="member-types">
			<?php foreach ( $member_types as $member_type ) : ?>

				<li id="member-type-<?php echo esc_attr( $member_type->name ); ?>" <?php echo ( $member_type->name === $current_member_type ) ? 'class="selected"' : ''; ?>>
					<?php bp_member_type_directory_link( $member_type->name ); ?>
				</li>

			<?php endforeach; ?>
		</ul>
	</li>
	<?php
}

/**
 * Outputs the Member Types navigation.
 *
 * @since 2.0.0
 */
function types_de_membre_directory_nav() {
	if ( ! bp_get_member_types() ) {
		return '';
	}

	wp_enqueue_style( 'types-de-membre' );
	wp_enqueue_script( 'types-de-membre' );

	// Get current member type.
	$current_member_type = bp_get_current_member_type();
	$active_descendant   = 'member-types-toggler';

	// Set the active member type if needed.
	if ( $current_member_type ) {
		$active_descendant = 'member-type-' . $current_member_type;
	}
	?>
	<ul class="component-navigation types-nav" role="listbox" aria-activedescendant="<?php echo esc_attr( $active_descendant ); ?>">
		<?php types_de_membre_directory_nav_items( $current_member_type ); ?>
	</ul>
	<?php
}
