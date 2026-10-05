<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 04.10.15
 * Time: 21:02
 */

namespace Palasthotel\Grid\WordPress;


class ReuseBox extends _Component
{
	function onCreate(){
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}
	function admin_menu(){
		add_submenu_page( 'grid_settings', 'Reusable boxes', 'Reusable boxes', 'edit_posts', 'grid_reuse_boxes', array( $this, 'render_reuse_boxes' ) );
		grid_wp_add_hidden_page( 'edit reuse box', 'edit reuse box', 'edit_posts', 'grid_edit_reuse_box', array( $this, 'edit_reuse_box' ) );
		$hook = grid_wp_add_hidden_page( 'Delete reusable box', 'Delete reusable box', 'edit_posts', 'grid_delete_reuse_box', array( $this, 'delete_reuse_box' ) );
		if ( $hook ) {
			add_action( 'load-' . $hook, array( $this, 'handle_delete_reuse_box' ) );
		}
	}

	function render_reuse_boxes() {
		$editor = $this->plugin->gridEditor->getReuseBoxEditor();
		grid_enqueue_editor_files($editor);
		$titles = array();
		$html = $editor->run( function( $id ) {
			return add_query_arg( array( 'page' => 'grid_edit_reuse_box', 'boxid' => $id ), admin_url( 'admin.php' ) );
		}, function( $id ) use ( &$titles ) {
			$titles[ $id ] = $this->reuse_title( $id );
			return grid_wp_reuse_delete_url( 'grid_delete_reuse_box', 'boxid', $id );
		});
		echo grid_wp_mark_reuse_delete_links( $html, 'grid_delete_reuse_box', 'boxid', $titles );
		grid_wp_print_reuse_delete_dialog(
			'grid_delete_reuse_box',
			'boxid',
			__( 'Delete this reusable box for good?', 'grid' ),
			/* translators: %s: title of the reusable box */
			__( 'Delete the reusable box "%s" for good?', 'grid' )
		);
	}

	function edit_reuse_box() {
		$boxid = intval($_GET['boxid']);
		$editor = $this->plugin->gridEditor->getReuseBoxEditor();
		grid_enqueue_editor_files($editor);
		grid_wp_load_js();
		$html = $editor->runEditor(
			$boxid,
			add_query_arg( array( 'noheader' => true, 'page' => 'grid_ckeditor_config' ), admin_url( 'admin.php' ) ),
			add_query_arg( array( 'noheader' => true, 'page' => 'grid_ajax' ), admin_url( 'admin.php' ) ),
			get_option( 'grid_debug_mode', false ),
			''
		);
		echo $html;
	}

	private function list_url() {
		return add_query_arg( array( 'page' => 'grid_reuse_boxes' ), admin_url( 'admin.php' ) );
	}

	/**
	 * Deletes before the admin page starts its output, so it can still redirect
	 * or answer with a status.
	 */
	function handle_delete_reuse_box() {
		$boxid   = isset( $_GET['boxid'] ) ? intval( $_GET['boxid'] ) : -1;
		$storage = $this->plugin->gridCore->storage;
		if ( in_array( (string) $boxid, array_map( 'strval', $storage->getReusedBoxIds() ), true ) ) {
			wp_die( esc_html__( 'This box is still in use.', 'grid' ), '', array( 'response' => 409, 'back_link' => true ) );
		}
		if ( empty( $_POST ) ) {
			return;
		}
		check_admin_referer( 'grid_delete_reuse_box_' . $boxid );
		if ( true === $this->plugin->gridEditor->getReuseBoxEditor()->runDelete( $storage, $boxid ) ) {
			wp_safe_redirect( $this->list_url() );
			exit;
		}
	}

	function delete_reuse_box() {
		$boxid = isset( $_GET['boxid'] ) ? intval( $_GET['boxid'] ) : -1;
		$html  = $this->plugin->gridEditor->getReuseBoxEditor()->runDelete( $this->plugin->gridCore->storage, $boxid );
		grid_wp_render_reuse_delete_page( $html, __( 'Delete reusable box', 'grid' ), 'grid_delete_reuse_box', $boxid, $this->list_url(), $this->reuse_title( $boxid ) );
	}

	/**
	 * @param int $id
	 *
	 * @return string the reusable box's title, empty if it has none
	 */
	private function reuse_title( $id ) {
		$element = $this->plugin->gridCore->storage->loadReuseBox( $id );
		return isset( $element->reusetitle ) ? (string) $element->reusetitle : '';
	}
}