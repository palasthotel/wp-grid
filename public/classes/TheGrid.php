<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 04.10.15
 * Time: 19:58
 */

namespace Palasthotel\Grid\WordPress;


use Grid\Constants\GridCSSVariant;
use Palasthotel\Grid\Core;
use const Grid\Constants\GRID_CSS_VARIANT_TABLE;

class TheGrid extends _Component {

    public Plugin $plugin;

	function onCreate(){
		add_action( 'admin_menu', array( $this, 'admin_menu') );

		add_action( 'wp_ajax_gridfrontendCSS', array($this, 'container_slots_css') );
		add_action( 'wp_ajax_nopriv_gridfrontendCSS', array( $this, 'container_slots_css' ) );

		add_filter( 'post_row_actions', array( $this, 'grid_wp_actions' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'grid_wp_actions' ), 10, 2 );

		add_action( 'grid_' . Core::FIRE_PUBLISH_GRID, array($this, 'clear_cache'));
	}

	/**
	 * @param int $postId
	 *
	 * @return string
	 */
	function getEditorUrl($postId){
		return add_query_arg( array( 'page' => 'grid', 'postid' => $postId ), admin_url( 'admin.php' ) );
	}

	/**
	 * add the grid action to post types
	 * @param $actions
	 * @param $entity
	 * @return array
	 */
	function grid_wp_actions( $actions, $entity ) {
		if ( true == get_option( 'grid_'.get_post_type().'_enabled', false ) ) {
			$temp = array();
			$editGridUrl = $this->getEditorUrl($entity->ID);
			$label = ($this->plugin->post->post_has_grid($entity->ID)) ? __("Edit Grid", Plugin::DOMAIN): __("Create Grid", Plugin::DOMAIN);
			$temp['grid'] = sprintf('<a href="%s">%s</a>', $editGridUrl, $label);
			$actions = array_merge( $temp, $actions );
		}
		return $actions;
	}

	function admin_menu(){
		add_submenu_page( "", 'The Grid', 'The Grid', 'edit_posts', 'grid', array( $this, 'render_grid' ) );
		add_submenu_page( "", 'Grid AJAX', 'The Grid AJAX', 'edit_posts', 'grid_ajax', array( $this, 'ajax' ) );
		add_submenu_page( "", 'Grid CKEditor Config', 'Grid CKEditor Config', 'edit_posts', 'grid_ckeditor_config', array( $this, 'ckeditor_config' ) );
		add_submenu_page( "", 'Grid Container slots CSS', 'Grid Conatiner slots CSS', 'edit_posts', 'grid_wp_container_slots_css', array( $this, 'container_slots_css' ) );
	}

	function render_grid() {
		global $wpdb;
		//	$storage=grid_wp_get_storage();
		$postid = intval($_GET['postid']);
		
		/**
		 * if there is no post id. we cannot do anytihng
		 */
		if( 0=== $postid ){
			?>
			<p><?php _e('Ups! I cannot find a Grid without an Post ID.', 'grid'); ?></p>
			<?php
			return;
		}
		
		/**
		 * look for grid id
		 */
		$rows = $wpdb->get_results( $wpdb->prepare( 'select grid_id from '.$wpdb->prefix.'grid_nodes where nid=%d', $postid ) );
		
		if ( 0 == count( $rows ) ) {
			$storage = $this->plugin->gridCore->storage;
			$id = $storage->createGrid();
			$grid = $storage->loadGrid( $id );
			if ( '__NONE__' != get_option( 'grid_default_container', '__NONE__' ) ) {
				$grid->insertContainer( get_option( 'grid_default_container' ), 0 );
			}
			$this->plugin->storageHelper->setPostGrid($postid, $id);
			$grid_id = $id;
		} else{
			$grid_id = $rows[0]->grid_id;
		}

		$post = get_post( $postid );

		grid_enqueue_editor_files();

		echo '<div class="wrap"><h2>'.$post->post_title.
			' <a title="Return to the post-edit page" class="add-new-h2"'.
			' href="'.admin_url("post.php?post=$postid&action=edit").'" >'.__('Edit Post', Plugin::DOMAIN).'</a'.
			'><a class="add-new-h2" href="'.
			get_permalink( $postid ).'">'.__('View Grid', Plugin::DOMAIN).'</a></h2> </div>';

		/**
		 * async parameters
		 */
		$async_service='';
		$async_domain='';
		$async_author='';
		$async_path='';
		$async_timeout = 60*5;
		if(get_option('grid_async',true))
		{
			/** @var \WP_User $user */
			$user=wp_get_current_user();
			$async_service = get_option("grid_async_url",'');
			if(''==$async_service)
			{
				$async_service = "https://async.the-grid.ws";
			}
			$async_domain= get_home_url();
			$async_author= (!empty($user->display_name))? $user->display_name : $user->user_login;
			$async_path="grid-post-id-".$postid;

			$async_timeout = get_option("grid_async_timeout",5*60);
		}


		$html = $this->plugin->gridEditor->getEditorHTML(
			$grid_id,
			'grid',
			add_query_arg( array( 'noheader' => true, 'page' => 'grid_ckeditor_config' ), admin_url( 'admin.php' ) ),
			add_query_arg( array( 'noheader' => true, 'page' => 'grid_ajax' ), admin_url( 'admin.php' ) ),
			get_option( 'grid_debug_mode', false ),
			add_query_arg( array( 'grid_preview' => true ), get_permalink( $postid ) ),
			add_query_arg( array( 'grid_preview' => true, 'grid_revision' => '{REV}' ), get_permalink( $postid ) ),
			$async_service,
			$async_domain,
			$async_author,
			$async_path,
		    $async_timeout
		);


		grid_wp_load_js();

		echo $html;
	}

	function ckeditor_config() {
		$styles = array();
		$formats = array();
		$ckeditor_plugins = array();

		$styles = apply_filters( 'grid_styles', $styles );
		$formats = apply_filters( 'grid_formats', $formats );
		$ckeditor_plugins = apply_filters('grid_ckeditor_plugins', $ckeditor_plugins);

		echo $this->plugin->gridEditor->getCKEditorConfig($styles, $formats, $ckeditor_plugins);
		die();
	}

	function container_slots_css() {
		global $wpdb;
		$defaultVariant = GridCSSVariant::getVariant(GRID_CSS_VARIANT_TABLE);
		$variant = (isset($_GET["variant"]))? sanitize_text_field($_GET["variant"]): $defaultVariant->slug();



		echo $this->plugin->gridEditor->getContainerSlotCSS(
			$wpdb->get_results('select * from '.$wpdb->prefix.'grid_container_type'),
			GridCSSVariant::getVariant($variant)
		);

		die();
	}

	const AJAX_NONCE_ACTION = 'grid_ajax';

	/**
	 * Endpoint methods that read site-wide settings and take no grid id.
	 */
	const AJAX_METHODS_WITHOUT_GRID = array( 'getContainerStyles', 'getSlotStyles', 'getBoxStyles', 'Rights' );

	/**
	 * The editor's requests carry this nonce in the X-Grid-Nonce header, see
	 * Plugin::enqueue_editor_files().
	 *
	 * @return string
	 */
	public static function create_ajax_nonce() {
		return wp_create_nonce( self::AJAX_NONCE_ACTION );
	}

	private function ajax_deny( $message ) {
		status_header( 403 );
		header( 'Content-Type: application/json; charset=UTF-8' );
		echo wp_json_encode( array( 'error' => $message ) );
		die();
	}

	/**
	 * A post's grid may only be changed by someone who may edit the post. Reusable
	 * boxes and containers ("box:<id>", "container:<id>") stay open to edit_posts,
	 * like their admin screens.
	 *
	 * @param string $method
	 * @param array $params
	 *
	 * @return bool
	 */
	private function current_user_can_call( $method, $params ) {
		// PHP method names are case-insensitive, and the editor calls getcontainerStyles
		if ( in_array( strtolower( $method ), array_map( 'strtolower', self::AJAX_METHODS_WITHOUT_GRID ), true ) ) {
			return true;
		}
		$grid_id = $params[0] ?? null;
		if ( ! is_scalar( $grid_id ) ) {
			return false;
		}
		$grid_id = (string) $grid_id;
		if ( preg_match( '/^(box|container):\d+$/', $grid_id ) === 1 ) {
			return true;
		}
		if ( preg_match( '/^\d+$/', $grid_id ) !== 1 ) {
			return false;
		}
		$post_id = $this->plugin->get_postid_by_grid( intval( $grid_id ) );
		if ( false === $post_id ) {
			return current_user_can( 'edit_others_posts' );
		}
		return current_user_can( 'edit_post', intval( $post_id ) );
	}

	function ajax() {
		$nonce = isset( $_SERVER['HTTP_X_GRID_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_GRID_NONCE'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::AJAX_NONCE_ACTION ) ) {
			$this->ajax_deny( 'invalid nonce' );
		}

		$json   = json_decode( file_get_contents( 'php://input' ) );
		$method = is_object( $json ) && isset( $json->method ) && is_string( $json->method ) ? $json->method : '';
		$params = is_object( $json ) && isset( $json->params ) && is_array( $json->params ) ? array_values( $json->params ) : array();
		if ( ! $this->current_user_can_call( $method, $params ) ) {
			$this->ajax_deny( 'not allowed' );
		}

		$this->plugin->gridAPI->handleAjaxCall();
		die();
	}

	/**
	 * save post with fresh modification time
	 * @param $grid_id
	 */
	public function clear_cache($grid_id){
		$post_id = grid_wp_get_postid_by_grid($grid_id);
		$post = array(
			'ID' => $post_id,
			'post_modified_gmt' => date( 'Y:m:d H:i:s' ),
		);
		wp_update_post( $post );
	}
}
