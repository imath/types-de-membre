<?php
/**
 * Plugin Template for the members directory nav.
 *
 * It's used when the BP Nouveau template pack is active.
 *
 * @package Types de membre
 * @subpackage \templates\buddypress\common\nav\directory-nav
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="bp-nav-wrapper">
	<nav class="<?php bp_nouveau_directory_type_navs_class(); ?>" role="navigation" aria-label="<?php esc_attr_e( 'Menu du répertoire des membres', 'types-de-membre' ); ?>">

		<?php if ( bp_nouveau_has_nav( array( 'object' => 'directory' ) ) ) : ?>

			<ul class="component-navigation <?php bp_nouveau_directory_list_class(); ?>">

				<?php
				while ( bp_nouveau_nav_items() ) :
					bp_nouveau_nav_item();
				?>

					<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php bp_nouveau_nav_classes(); ?>" <?php bp_nouveau_nav_scope(); ?> data-bp-object="<?php bp_nouveau_directory_nav_object(); ?>">
						<a href="<?php bp_nouveau_nav_link(); ?>">
							<?php bp_nouveau_nav_link_text(); ?>

							<?php if ( bp_nouveau_nav_has_count() ) : ?>
								<span class="count"><?php bp_nouveau_nav_count(); ?></span>
							<?php endif; ?>
						</a>
					</li>

				<?php endwhile; ?>

			</ul><!-- .component-navigation -->

			<?php types_de_membre_directory_nav(); ?>

		<?php endif; ?>

	</nav><!-- .bp-navs -->
</div><!-- .bp-nav-wrapper -->
