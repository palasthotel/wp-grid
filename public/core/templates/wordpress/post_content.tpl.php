<?php
/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid\Wordpress
 */
?>
<h3><?php the_title(); ?></h3>
<?php
if ( 'full' === $this->content->viewmode ) {
	the_content();
} elseif ( 'excerpt' === $this->content->viewmode ) {
	the_excerpt();
} elseif ( grid_wp_show_box_errors( $this ) ) {
?><p>Unsupported Viewmode: <?php echo esc_html( $this->content->viewmode ); ?></p><?php
} ?>
