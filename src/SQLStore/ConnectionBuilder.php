<?php

namespace SMW\SQLStore;

use DatabaseSqlite;
use Doctrine\DBAL\DriverManager;

/**
 * Builds a DBAL Connection object from a MediaWiki configuration array.
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ConnectionBuilder {

	private $config;

	public function __construct( $config ) {
		$this->config = $config;
	}

	public function newConnection() {
		return DriverManager::getConnection( $this->getConnectionParams() );
	}

	private function getConnectionParams() {
		switch ( $this->config['wgDBtype'] ) {
			case 'mysql':
				return $this->getMySQLParams();
			case 'sqlite':
				return $this->getSQLiteParams();
			case 'postgres':
				return $this->getPostgresParams();
		}

		throw new \RuntimeException( 'Unsupported database type' );
	}

	private function getMySQLParams() {
		return array(
			'driver' => 'pdo_mysql',
			'user' => $this->config['wgDBuser'],
			'password' => $this->config['wgDBpassword'],
			'host' => $this->config['wgDBserver'],
			'dbname' => $this->config['wgDBname']
		);
	}

	private function getSQLiteParams() {
		$path = DatabaseSqlite::generateFileName(
			$this->config['wgSQLiteDataDir'],
			$this->config['wgDBname']
		);
		return array(
			'driver' => 'pdo_sqlite',
			'user' => $this->config['wgDBuser'],
			'password' => $this->config['wgDBpassword'],
			'path' => $path
		);
	}

	private function getPostgresParams() {
		return array(
			'driver' => 'pdo_pgsql',
			'user' => $this->config['wgDBuser'],
			'password' => $this->config['wgDBpassword'],
			'host' => $this->config['wgDBserver'],
			'dbname' => $this->config['wgDBname']
		);
	}

}