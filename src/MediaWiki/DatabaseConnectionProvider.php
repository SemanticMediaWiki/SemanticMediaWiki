<?php

namespace SMW\MediaWiki;

use SMW\DBConnectionProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class DatabaseConnectionProvider implements DBConnectionProvider, LoggerAwareInterface {

	/**
	 * @var string
	 */
	private $provider;

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var DBConnectionProvider[]
	 */
	private $connectionProviders = array();

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var array
	 */
	private $localConnectionConf = array();

	/**
	 * @var boolean
	 */
	private $resetTransactionProfiler = false;

	/**
	 * @since 3.0
	 *
	 * @param string|null $provider
	 */
	public function __construct( $provider = null ) {
		$this->provider = $provider;
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 3.0
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @see #2532
	 *
	 * @param array $localConnectionConf
	 *
	 * @since 3.0
	 */
	public function setLocalConnectionConf( array $localConnectionConf ) {
		$this->localConnectionConf = $localConnectionConf;
	}

	/**
	 * @see DBConnectionProvider::getConnection
	 *
	 * @since 2.1
	 *
	 * @return Database
	 */
	public function getConnection() {

		if ( $this->connection !== null ) {
			return $this->connection;
		}

		// Default configuration
		$connectionConf = array(
			'read'  => DB_SLAVE,
			'write' => DB_MASTER
		);

		if ( isset( $this->localConnectionConf[$this->provider] ) ) {
			$connectionConf = $this->localConnectionConf[$this->provider];
		}

		return $this->connection = $this->createConnection( $connectionConf );
	}

	/**
	 * @see #1499, #1603
	 *
	 * @since 2.4
	 */
	public function resetTransactionProfiler() {
		$this->resetTransactionProfiler = true;
	}

	/**
	 * @see DBConnectionProvider::releaseConnection
	 *
	 * @since 2.1
	 */
	public function releaseConnection() {

		foreach ( $this->connectionProviders as $connectionProvider ) {
			$connectionProvider->releaseConnection();
		}

		$this->connection = null;
	}

	private function createConnection( $connectionConf ) {

		if ( isset( $connectionConf['callback'] ) && is_callable( $connectionConf['callback'] ) ) {
			return call_user_func( $connectionConf['callback'] );
		}

		if ( !isset( $connectionConf['read'] ) || !isset( $connectionConf['write'] ) ) {
			throw new RuntimeException( "The configuration is incomplete (required read, write identifier)." );
		}

		$this->connectionProviders['read'] = new LazyDBConnectionProvider(
			$connectionConf['read']
		);

		if ( $connectionConf['read'] === $connectionConf['write'] ) {
			$this->connectionProviders['write'] = $this->connectionProviders['read'];
		} else {
			$this->connectionProviders['write'] = new LazyDBConnectionProvider(
				$connectionConf['write']
			);
		}

		$connection = new Database(
			$this->connectionProviders['read'],
			$this->connectionProviders['write']
		);

		// Only required because of SQlite
		$connection->setDBPrefix( $GLOBALS['wgDBprefix'] );

		// Has no effect with 1.28+
		// https://github.com/wikimedia/mediawiki/commit/5c681e438fd162ac6b140e07e15f2dbc1393a775
		$connection->resetTransactionProfiler( $this->resetTransactionProfiler );

		if ( $this->logger !== null ) {
			$this->logger->info( "[$this->provider] connection provider with " . json_encode( $connectionConf ) );
		}

		return $connection;
	}

}
