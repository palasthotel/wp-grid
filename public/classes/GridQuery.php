<?php


namespace Palasthotel\Grid\WordPress;


use mysqli;
use mysqli_result;
use Palasthotel\Grid\AbstractQuery;

/**
 * Class GridQuery
 * @package Palasthotel\Grid\WordPress
 */
class GridQuery extends AbstractQuery {

	/**
	 * @return string
	 */
	public function prefix() {
		global $wpdb;
		return $wpdb->prefix;
	}

	/**
	 * @var mysqli|null
	 */
	public $connection = null;

	/**
	 * true if the connection is our own and not the one of $wpdb
	 * @var bool
	 */
	private bool $own_connection = false;

	/**
	 * The grid library works with mysqli results, so it uses the mysqli connection of
	 * $wpdb: same charset (utf8mb4), host, port, socket and SSL settings as WordPress.
	 * Only a db drop-in without a mysqli connection gets a connection of its own.
	 *
	 * @return mysqli
	 */
	public function getConnection() {
		if ( $this->connection instanceof mysqli ) {
			return $this->connection;
		}
		global $wpdb;
		$wpdb->check_connection( false );
		if ( $wpdb->dbh instanceof mysqli ) {
			$this->connection = $wpdb->dbh;
			return $this->connection;
		}

		$host_data = $wpdb->parse_db_host( DB_HOST );
		list( $host, $port, $socket, $is_ipv6 ) = $host_data ? $host_data : array( DB_HOST, null, null, false );
		if ( $is_ipv6 && extension_loaded( 'mysqlnd' ) ) {
			$host = "[$host]";
		}
		$connection = mysqli_init();
		$connection->real_connect( $host, DB_USER, DB_PASSWORD, DB_NAME, $port ? intval( $port ) : null, $socket, defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0 );
		if ( $connection->connect_errno ) {
			error_log( "WP Grid: " . $connection->connect_error, 4 );
			wp_die( "WP Grid could not connect to database." );
		}
		$connection->set_charset( $wpdb->charset ? $wpdb->charset : 'utf8mb4' );
		$this->connection     = $connection;
		$this->own_connection = true;
		return $connection;
	}

	/**
	 * @param string $sql
	 *
	 * @return mysqli_result|bool
	 */
	public function execute( $sql ) {
		return $this->getConnection()->query($sql);
	}

	/**
	 * @param string $str
	 *
	 * @return string
	 */
	public function real_escape_string( $str ) {
		return $this->getConnection()->real_escape_string($str);
	}

	/**
	 * on object destruction
	 */
	public function __destruct(){
		// never close the connection of $wpdb
		if ( $this->own_connection && $this->connection ) {
			$this->connection->close();
		}
	}
}
