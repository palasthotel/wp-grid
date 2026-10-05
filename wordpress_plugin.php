<?php
/**
 * Plugin Name: Grid - DEV
 * Description: Development wrapper that loads public/; never shipped.
 * Version: X.X.X
 * Author: Palasthotel <webmaster@palasthotel.de>
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: grid
 */

defined( 'ABSPATH' ) || exit;

use Palasthotel\Grid\WordPress\Plugin;

include dirname(__FILE__) . "/public/wordpress_plugin.php";

register_activation_hook(__FILE__, function($multisite){
	Plugin::instance()->onActivation($multisite);
});

register_deactivation_hook(__FILE__, function($multisite){
	Plugin::instance()->onDeactivation($multisite);
});