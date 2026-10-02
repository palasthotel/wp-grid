<?php


namespace Palasthotel\Grid\WordPress;


class StorageHelper {

    public Plugin $plugin;
    /**
     * @var \wpdb
     */
    public mixed $wpdb;

	/**
	 * Grid constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct($plugin) {
		$this->plugin = $plugin;
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * @param int $postid
	 * @param int $gridid
	 *
	 * @return bool|int
	 */
	public function setPostGrid($postid, $gridid){
		return $this->wpdb->query(
			$this->wpdb->prepare(
				'insert into '.$this->wpdb->prefix.'grid_nodes (nid,grid_id) values (%d,%d)',
				intval( $postid ),
				intval( $gridid )
			)
		);
	}

}