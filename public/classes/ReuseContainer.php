<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 04.10.15
 * Time: 20:39
 */

namespace Palasthotel\Grid\WordPress;


class ReuseContainer extends _Component
{
	function onCreate(){
		add_action( 'admin_menu', array($this, 'admin_menu' ) );
	}
	function admin_menu(){
		add_submenu_page( 'grid_settings', 'reusable container', 'Reusable container', 'edit_posts', 'grid_reuse_containers', array( $this, 'reuse_containers' ) );
		grid_wp_add_hidden_page( 'edit reuse container', 'edit reuse container', 'edit_posts', 'grid_edit_reuse_container', array( $this, 'edit_reuse_container' ) );
		$hook = grid_wp_add_hidden_page( 'Delete reusable container', 'Delete reusable container', 'edit_posts', 'grid_delete_reuse_container', array( $this, 'delete_reuse_container' ) );
		if ( $hook ) {
			add_action( 'load-' . $hook, array( $this, 'handle_delete_reuse_container' ) );
		}
	}
	function reuse_containers() {
		$storage = $this->plugin->gridCore->storage;

		$editor = $this->plugin->gridEditor->getReuseContainerEditor();
		grid_enqueue_editor_files($editor);
		$titles = array();
		$html = $editor->run( $storage, function( $id ) {
			return add_query_arg( array( 'page' => 'grid_edit_reuse_container', 'containerid' => $id ), admin_url( 'admin.php' ) );
		}, function( $id ) use ( &$titles ) {
			$titles[ $id ] = $this->reuse_title( $id );
			return grid_wp_reuse_delete_url( 'grid_delete_reuse_container', 'containerid', $id );
		} );
		echo grid_wp_mark_reuse_delete_links( $html, 'grid_delete_reuse_container', 'containerid', $titles );
		grid_wp_print_reuse_delete_dialog(
			'grid_delete_reuse_container',
			'containerid',
			__( 'Delete this reusable container for good?', 'grid' ),
			/* translators: %s: title of the reusable container */
			__( 'Delete the reusable container "%s" for good?', 'grid' )
		);
	}
	function edit_reuse_container() {
		$containerid = intval($_GET['containerid']);

		$editor = $this->plugin->gridEditor->getReuseContainerEditor();
		grid_enqueue_editor_files( $editor );
		grid_wp_load_js();
		$html = $editor->runEditor(
			$containerid,
			add_query_arg( array( 'noheader' => true, 'page' => 'grid_ckeditor_config' ), admin_url( 'admin.php' ) ),
			add_query_arg( array( 'noheader' => true, 'page' => 'grid_ajax' ), admin_url( 'admin.php' ) ),
			get_option( 'grid_debug_mode', false ),
			''
		);
		echo $html;
	}

	private function list_url() {
		return add_query_arg( array( 'page' => 'grid_reuse_containers' ), admin_url( 'admin.php' ) );
	}

	/**
	 * Deletes before the admin page starts its output, so it can still redirect
	 * or answer with a status.
	 */
	function handle_delete_reuse_container() {
		$containerid = isset( $_GET['containerid'] ) ? intval( $_GET['containerid'] ) : -1;
		$storage     = $this->plugin->gridCore->storage;
		if ( in_array( (string) $containerid, array_map( 'strval', $storage->getReusedContainerIds() ), true ) ) {
			wp_die( esc_html__( 'This container is still in use.', 'grid' ), '', array( 'response' => 409, 'back_link' => true ) );
		}
		if ( empty( $_POST ) ) {
			return;
		}
		check_admin_referer( 'grid_delete_reuse_container_' . $containerid );
		if ( true === $this->plugin->gridEditor->getReuseContainerEditor()->runDelete( $storage, $containerid ) ) {
			wp_safe_redirect( $this->list_url() );
			exit;
		}
	}

	function delete_reuse_container() {
		$containerid = isset( $_GET['containerid'] ) ? intval( $_GET['containerid'] ) : -1;
		$html        = $this->plugin->gridEditor->getReuseContainerEditor()->runDelete( $this->plugin->gridCore->storage, $containerid );
		grid_wp_render_reuse_delete_page( $html, __( 'Delete reusable container', 'grid' ), 'grid_delete_reuse_container', $containerid, $this->list_url(), $this->reuse_title( $containerid ) );
	}

	/**
	 * @param int $id
	 *
	 * @return string the reusable container's title, empty if it has none
	 */
	private function reuse_title( $id ) {
		$element = $this->plugin->gridCore->storage->loadReuseContainer( $id );
		return isset( $element->reusetitle ) ? (string) $element->reusetitle : '';
	}
}