<?php
/**
 * Replaces the grid library's template for error boxes, e.g. a reused box that
 * was deleted: only shown while debugging or to someone who may edit the grid.
 *
 * @author Palasthotel <rezeption@palasthotel.de>
 * @license GPL-3.0-or-later https://www.gnu.org/licenses/gpl-3.0.html
 * @package Palasthotel\Grid\Wordpress
 */

if ( ! grid_wp_show_box_errors( $this ) ) {
	return;
}

$classes = $this->classes;
array_push( $classes, 'grid-box' );

if ( ! empty( $this->style ) ) {
	array_push( $classes, $this->style );
}

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php echo esc_html( $content ); ?>
</div>
