<?php

use Palasthotel\Grid\Endpoint;
use Palasthotel\Grid\Storage;
use Palasthotel\Grid\WordPress\Plugin;

/**
 * @return \Palasthotel\Grid\Wordpress\Plugin
 */
function grid_plugin(){
	return Plugin::instance();
}

/**
 * Registers an admin page that has no menu entry, like the grid editor.
 *
 * WordPress looks a page's title up in its menu, so a page without a parent menu has none
 * and admin-header.php passes null to strip_tags() - a deprecation notice on PHP 8.1+.
 * Setting the title when the page loads avoids that.
 *
 * @return string|false the page's hook suffix
 */
function grid_wp_add_hidden_page( $page_title, $menu_title, $capability, $menu_slug, $callback ) {
	$hook = add_submenu_page( '', $page_title, $menu_title, $capability, $menu_slug, $callback );
	if ( $hook ) {
		add_action( 'load-' . $hook, function () use ( $page_title ) {
			$GLOBALS['title'] = $page_title;
		} );
	}
	return $hook;
}

/**
 * drupal t function
 *
 */
// TODO: use grid translate function
if ( ! function_exists( 't' ) ) {
	function t( $str ) {
		return __( $str, Plugin::DOMAIN );
	}
}

/**
 * get postid by grid id
 * deprecated use
 * global $grid_plugin->get_postid_by_grid
 * @param $gridid
 * @return mixed
 */
function grid_wp_get_postid_by_grid($gridid) {
	return grid_plugin()->get_postid_by_grid($gridid);
}

/**
 * get grid id by post id
 * deprecated use
 * global $grid_plugin->get_grid_by_postid
 * @param $postid
 * @return bool
 */
function grid_wp_get_grid_by_postid( $postid ) {
	return grid_plugin()->get_grid_by_postid($postid);
}

/**
 * loads grid by post
 * deprecated use
 * global $grid_plugin->grid_load
 * @param $post
 */
function grid_wp_load($post){
	grid_plugin()->grid_load($post);
}

/**
 * The delete link of a reusable box or container. It carries a nonce, so the
 * list can delete right after a confirmation dialog.
 *
 * @param string $page  admin page that deletes
 * @param string $param query parameter with the id
 * @param int $id
 *
 * @return string
 */
function grid_wp_reuse_delete_url( $page, $param, $id ) {
	return wp_nonce_url(
		add_query_arg( array( 'page' => $page, $param => $id ), admin_url( 'admin.php' ) ),
		$page . '_' . intval( $id )
	);
}

/**
 * Asks before deleting from the list of reusable boxes or containers and then
 * posts the deletion; without JavaScript the link opens the confirmation page.
 *
 * @param string $page  admin page that deletes
 * @param string $param query parameter with the id
 * @param string $message
 */
function grid_wp_print_reuse_delete_dialog( $page, $param, $message ) {
	$config = wp_json_encode( array( 'page' => $page, 'param' => $param, 'message' => $message ) );
	wp_print_inline_script_tag( <<<JS
(function (config) {
	document.addEventListener('click', function (event) {
		var link = event.target.closest('a[href*="page=' + config.page + '&"], a[href*="page=' + config.page + '&amp;"]');
		if (!link) {
			return;
		}
		event.preventDefault();
		if (!window.confirm(config.message)) {
			return;
		}
		var url = new URL(link.href);
		var form = document.createElement('form');
		form.method = 'post';
		form.action = link.href;
		[['grid_delete_id', url.searchParams.get(config.param)], ['_wpnonce', url.searchParams.get('_wpnonce')]].forEach(function (field) {
			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = field[0];
			input.value = field[1];
			form.appendChild(input);
		});
		document.body.appendChild(form);
		form.submit();
	});
})($config);
JS
	);
}

/**
 * The confirmation page for deleting a reusable box or container, inside the
 * admin layout: the library's form gets a nonce, a WordPress button and a way back.
 *
 * @param string $html  the library's confirmation form
 * @param string $title
 * @param string $page  admin page that deletes
 * @param int $id
 * @param string $list_url
 */
function grid_wp_render_reuse_delete_page( $html, $title, $page, $id, $list_url ) {
	$html = str_replace(
		array( 'class="form-submit"', '</form>' ),
		array(
			'class="button button-primary"',
			wp_nonce_field( $page . '_' . intval( $id ), '_wpnonce', true, false ) .
			' <a class="button" href="' . esc_url( $list_url ) . '">' . esc_html__( 'Cancel', 'grid' ) . '</a></form>',
		),
		$html
	);
	echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>';
	echo '<p>' . esc_html__( 'This deletes it for good.', 'grid' ) . '</p>';
	echo $html;
	echo '</div>';
}

/**
 * Whether the front end may show a box's error, e.g. a deleted box or an
 * unsupported viewmode: while debugging, or to someone who may edit the grid's
 * post and so fix it. Everybody else gets nothing.
 *
 * @param grid_box|null $box
 *
 * @return bool
 */
function grid_wp_show_box_errors( $box = null ) {
	$post_id = 0;
	if ( isset( $box->grid->gridid ) ) {
		$post_id = intval( grid_wp_get_postid_by_grid( $box->grid->gridid ) );
	}
	if ( ! $post_id ) {
		$post_id = get_queried_object_id();
	}
	$show = ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY )
		|| get_option( 'grid_debug_mode', false );
	if ( ! $show && is_user_logged_in() ) {
		$show = $post_id ? current_user_can( 'edit_post', $post_id ) : current_user_can( 'edit_posts' );
	}
	return (bool) apply_filters( 'grid_show_box_errors', $show, $box, $post_id );
}

/**
 * get grid privileges
 * @return mixed
 */
function grid_wp_get_privs() {
	global $wp_roles;
	$names = $wp_roles->get_names();
	$ajaxendpoint = new Endpoint();
	$rights = $ajaxendpoint->Rights();
	$default = array();
	foreach ( $rights as $right ) {
		$defaults['administrator'][ $right ] = true;
		$defaults['editor'][ $right ] = true;
	}
	foreach ( $names as $key => $name ) {
		if ( ! isset( $defaults[ $key ] ) ) {
			foreach ( $rights as $right ) {
				$defaults[ $key ][ $right ] = false;
			}
		}
	}
	$privileges = get_option( 'grid_privileges', $defaults );
	return $privileges;
}



/**
 * deprecated function
 * use global $grid_plugin->get_storage()
 * @return Storage grid_storage
 */
function grid_wp_get_storage() {
	return grid_plugin()->gridCore->storage;
}


function grid_wp_load_js() {
	// for wp.media
	wp_enqueue_script('jquery');
	if ( function_exists( 'wp_enqueue_media' ) ) {
		wp_enqueue_media();
	} else {
		wp_enqueue_style( 'thickbox' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
	}
}


/**
 * get db connection
 * deprecated use
 * global $grid_plugin->get_db_connection
 * @return mysqli
 */
function grid_wp_get_mysqli() {
	return grid_plugin()->gridQuery->getConnection();
}


function grid_modify_front_pages_dropdown()
{
	// Filtering /wp-includes/post-templates.php#L780
	add_filter( 'get_pages', 'grid_add_landing_page_to_pages_on_front' );
}

function grid_add_landing_page_to_pages_on_front( $r )
{
	$args = array(
		'post_type' => 'landing_page',
	);
	$stacks = get_posts( $args );
	$r = array_merge( $r, $stacks );

	return $r;
}

function grid_enable_front_page_landing_page( $query )
{
	if ( ( ! isset($query->query_vars['post_type'] ) || $query->query_vars['post_type'] == '' ) && 0 != $query->query_vars['page_id'] ) {
		$query->query_vars['post_type'] = array( 'page', 'landing_page' );
	}
}

/**
 * returns additional editor widget files
 * @return  array js and css key are arrays of file paths
 */
function grid_get_additional_editor_widgets(){
	return apply_filters('grid_editor_widgets', array( "js" => array(), "css"=> array() ) );
}
/**
 * enqueue js and css files for editor
 */
function grid_enqueue_editor_files($editor = null){
	grid_plugin()->enqueue_editor_files($editor);
}

/**
 * get language
 * deprecated use
 * global $grid_plugin->get_lang
 * @return string
 */
function grid_get_lang(){
	return grid_plugin()->get_lang();
}