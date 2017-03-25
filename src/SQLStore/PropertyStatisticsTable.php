<?php

namespace SMW\SQLStore;

use MWException;
use SMW\MediaWiki\Database;
use SMW\Store\PropertyStatisticsStore;
use SMW\SQLStore\Exception\PropertyStatisticsInvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Simple implementation of PropertyStatisticsTable using MediaWikis
 * database abstraction layer and a single table.
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nischay Nahata
 */
class PropertyStatisticsTable implements PropertyStatisticsStore, LoggerAwareInterface {

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var boolean
	 */
	private $isCommandLineMode = false;

	/**
	 * @var boolean
	 */
	private $onTransactionIdle = false;

	/**
	 * @since 1.9
	 *
	 * @param Database $connection
	 * @param string $table
	 */
	public function __construct( Database $connection, $table ) {
		assert( is_string( $table ) );

		$this->connection = $connection;
		$this->table = $table;
	}

	/**
	 * @see LoggerAwareInterface::setLogger
	 *
	 * @since 2.5
	 *
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
	 * Indicates whether MW is running in command-line mode or not.
	 *
	 * @since 2.5
	 *
	 * @param boolean $isCommandLineMode
	 */
	public function isCommandLineMode( $isCommandLineMode ) {
		$this->isCommandLineMode = $isCommandLineMode;
	}

	/**
	 * @since 2.5
	 */
	public function waitOnTransactionIdle() {
		$this->onTransactionIdle = !$this->isCommandLineMode;
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getStatisticsTable() {
		return $this->table;
	}

	/**
	 * @see PropertyStatisticsStore::addToUsageCount
	 *
	 * @since 1.9
	 *
	 * @param integer $propertyId
	 * @param integer $value
	 *
	 * @return boolean Success indicator
	 * @throws PropertyStatisticsInvalidArgumentException
	 */
	public function addToUsageCount( $propertyId, $value ) {

		if ( !is_int( $value ) ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The value to add must be an integer' );
		}

		if ( !is_int( $propertyId ) || $propertyId <= 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The property id to add must be a positive integer' );
		}

		if ( $value == 0 ) {
			return true;
		}

		try {
			$this->connection->update(
				$this->table,
				array(
					'usage_count = usage_count ' . ( $value > 0 ? '+ ' : '- ' ) . $this->connection->addQuotes( abs( $value ) ),
				),
				array(
					'p_id' => $propertyId
				),
				__METHOD__
			);
		} catch ( \DBQueryError $e ) {
			// #2345 Do nothing as it most likely an "Error: 1264 Out of range
			// value for column" in strict mode
			// As an unsigned int, we expected it to be 0
			$this->setUsageCount( $propertyId, 0 );
		}

		return true;
	}

	/**
	 * @see PropertyStatisticsStore::addToUsageCounts
	 *
	 * @since 1.9
	 *
	 * @param array $additions
	 *
	 * @return boolean Success indicator
	 */
	public function addToUsageCounts( array $additions ) {

		$success = true;

		if ( $additions === array() ) {
			return $success;
		}

		$method = __METHOD__;

		if ( $this->onTransactionIdle ) {
			$this->connection->onTransactionIdle( function () use( $method, $additions ) {
				$this->log( $method . ' (onTransactionIdle)' );
				$this->onTransactionIdle = false;
				$this->addToUsageCounts( $additions );
			} );

			return $success;
		}

		// TODO: properly implement this
		foreach ( $additions as $propertyId => $addition ) {
			$success = $this->addToUsageCount( $propertyId, $addition ) && $success;
		}

		return $success;
	}

	/**
	 * @see PropertyStatisticsStore::setUsageCount
	 *
	 * @since 1.9
	 *
	 * @param integer $propertyId
	 * @param integer $value
	 *
	 * @return boolean Success indicator
	 * @throws PropertyStatisticsInvalidArgumentException
	 */
	public function setUsageCount( $propertyId, $value ) {

		if ( !is_int( $value ) || $value < 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The value to add must be a positive integer' );
		}

		if ( !is_int( $propertyId ) || $propertyId <= 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The property id to add must be a positive integer' );
		}

		return $this->connection->update(
			$this->table,
			array(
				'usage_count' => $value,
			),
			array(
				'p_id' => $propertyId
			),
			__METHOD__
		);
	}

	/**
	 * @see PropertyStatisticsStore::insertUsageCount
	 *
	 * @since 1.9
	 *
	 * @param integer $propertyId
	 * @param integer $value
	 *
	 * @return boolean Success indicator
	 * @throws PropertyStatisticsInvalidArgumentException
	 */
	public function insertUsageCount( $propertyId, $value ) {

		if ( !is_int( $value ) || $value < 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The value to add must be a positive integer' );
		}

		if ( !is_int( $propertyId ) || $propertyId <= 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The property id to add must be a positive integer' );
		}

		return $this->connection->insert(
			$this->table,
			array(
				'usage_count' => $value,
				'p_id' => $propertyId,
			),
			__METHOD__
		);
	}

	/**
	 * @since 2.2
	 *
	 * @param integer $propertyId
	 *
	 * @return integer
	 */
	public function getUsageCount( $propertyId ) {

		if ( !is_int( $propertyId ) ) {
			return 0;
		}

		$row = $this->connection->selectRow(
			$this->table,
			array(
				'usage_count'
			),
			array(
				'p_id' => $propertyId,
			),
			__METHOD__
		);

		return $row !== false ? (int)$row->usage_count : 0;
	}

	/**
	 * @see PropertyStatisticsStore::getUsageCounts
	 *
	 * @since 1.9
	 *
	 * @param array $propertyIds
	 *
	 * @return array
	 */
	public function getUsageCounts( array $propertyIds ) {
		if ( $propertyIds === array() ) {
			return array();
		}

		$propertyStatistics = $this->connection->select(
			$this->connection->tablename( $this->table ),
			array(
				'usage_count',
				'p_id',
			),
			array(
				'p_id' => $propertyIds,
			),
			__METHOD__
		);

		$usageCounts = array();

		foreach ( $propertyStatistics as $propertyStatistic ) {
			assert( ctype_digit( $propertyStatistic->p_id ) );
			assert( ctype_digit( $propertyStatistic->usage_count ) );

			$usageCounts[(int)$propertyStatistic->p_id] = (int)$propertyStatistic->usage_count;
		}

		return $usageCounts;
	}

	/**
	 * @see PropertyStatisticsStore::deleteAll
	 *
	 * @since 1.9
	 *
	 * @return boolean Success indicator
	 */
	public function deleteAll() {
		return $this->connection->delete(
			$this->table,
			'*',
			__METHOD__
		);
	}

	private function log( $message, $context = array() ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
