<?php

namespace SMW\SQLStore;

use MWException;
use Psr\Log\LoggerAwareTrait;
use SMW\MediaWiki\Database;
use SMW\SQLStore\Exception\PropertyStatisticsInvalidArgumentException;

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
class PropertyStatisticsStore {

	use LoggerAwareTrait;

	/**
	 * @var Database
	 */
	private $connection;

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
	 */
	public function __construct( Database $connection ) {
		$this->connection = $connection;
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
		return SQLStore::PROPERTY_STATISTICS_TABLE;
	}

	/**
	 * Change the usage count for the property of the given ID by the given
	 * value. The method does nothing if the count is 0.
	 *
	 * @since 1.9
	 *
	 * @param integer $propertyId
	 * @param integer $value
	 *
	 * @return boolean Success indicator
	 */
	public function addToUsageCount( $pid, $value ) {

		$usageVal = 0;
		$nullVal = 0;

		if ( is_array( $value ) ) {
			$usageVal = $value[0];
			$nullVal = $value[1];
		} else {
			$usageVal = $value;
		}

		if ( !is_int( $usageVal ) || !is_int( $nullVal ) ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The value to add must be an integer' );
		}

		if ( !is_int( $pid ) || $pid <= 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The property id to add must be a positive integer' );
		}

		if ( $usageVal == 0 && $nullVal == 0 ) {
			return true;
		}

		try {
			$this->connection->update(
				SQLStore::PROPERTY_STATISTICS_TABLE,
				[
					'usage_count = usage_count ' . ( $usageVal > 0 ? '+ ' : '- ' ) . $this->connection->addQuotes( abs( $usageVal ) ),
					'null_count = null_count ' . ( $nullVal > 0 ? '+ ' : '- ' ) . $this->connection->addQuotes( abs( $nullVal ) ),
				],
				[
					'p_id' => $pid
				],
				__METHOD__
			);
		} catch ( \DBQueryError $e ) {
			// #2345 Do nothing as it most likely an "Error: 1264 Out of range
			// value for column" in strict mode
			// As an unsigned int, we expected it to be 0
			$this->setUsageCount( $pid, [ 0, 0 ] );
		}

		return true;
	}

	/**
	 * Increase the usage counts of multiple properties.
	 *
	 * The $additions parameter should be an array with integer
	 * keys that are property ids, and associated integer values
	 * that are the amount the usage count should be increased.
	 *
	 * @since 1.9
	 *
	 * @param array $additions
	 *
	 * @return boolean Success indicator
	 */
	public function addToUsageCounts( array $additions ) {

		$success = true;

		if ( $additions === [] ) {
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

		foreach ( $additions as $pid => $addition ) {

			if ( is_array( $addition ) ) {
				// We don't check this, have it fail in case this isn't set correctly
				$addition = [ $addition['usage'], $addition['null'] ];
			}

			$success = $this->addToUsageCount( $pid, $addition ) && $success;
		}

		return $success;
	}

	/**
	 * Updates an existing usage count.
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

		$usageCount = 0;
		$nullCount = 0;

		if ( is_array( $value ) ) {
			$usageCount = $value[0];
			$nullCount = $value[1];
		} else {
			$usageCount = $value;
		}

		if ( !is_int( $usageCount ) || $usageCount < 0 || !is_int( $nullCount ) || $nullCount < 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The value to add must be a positive integer' );
		}

		if ( !is_int( $propertyId ) || $propertyId <= 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The property id to add must be a positive integer' );
		}

		return $this->connection->update(
			SQLStore::PROPERTY_STATISTICS_TABLE,
			[
				'usage_count' => $usageCount,
				'null_count' => $nullCount,
			],
			[
				'p_id' => $propertyId
			],
			__METHOD__
		);
	}

	/**
	 * Adds a new usage count.
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

		$usageCount = 0;
		$nullCount = 0;

		if ( is_array( $value ) ) {
			$usageCount = $value[0];
			$nullCount = $value[1];
		} else {
			$usageCount = $value;
		}

		if ( !is_int( $usageCount ) || $usageCount < 0 || !is_int( $nullCount ) || $nullCount < 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The value to add must be a positive integer' );
		}

		if ( !is_int( $propertyId ) || $propertyId <= 0 ) {
			throw new PropertyStatisticsInvalidArgumentException( 'The property id to add must be a positive integer' );
		}

		try {
			$this->connection->insert(
				SQLStore::PROPERTY_STATISTICS_TABLE,
				[
					'usage_count' => $usageCount,
					'null_count' => $nullCount,
					'p_id' => $propertyId,
				],
				__METHOD__
			);
		} catch ( \DBQueryError $e ) {
			// Most likely hit "Error: 1062 Duplicate entry ..."
			$this->setUsageCount( $propertyId, $value );
		}

		return true;
	}

	/**
	 * Returns the usage count for a provided property id.
	 *
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
			SQLStore::PROPERTY_STATISTICS_TABLE,
			[
				'usage_count'
			],
			[
				'p_id' => $propertyId,
			],
			__METHOD__
		);

		return $row !== false ? (int)$row->usage_count : 0;
	}

	/**
	 * Returns the usage counts of the provided properties.
	 *
	 * The returned array contains integer keys which are property ids,
	 * with the associated values being their usage count (also integers).
	 *
	 * Properties for which no usage count is found will not have
	 * an entry in the result array.
	 *
	 * @since 1.9
	 *
	 * @param array $propertyIds
	 *
	 * @return array
	 */
	public function getUsageCounts( array $propertyIds ) {
		if ( $propertyIds === [] ) {
			return [];
		}

		$propertyStatistics = $this->connection->select(
			$this->connection->tablename( SQLStore::PROPERTY_STATISTICS_TABLE ),
			[
				'usage_count',
				'p_id',
			],
			[
				'p_id' => $propertyIds,
			],
			__METHOD__
		);

		$usageCounts = [];

		foreach ( $propertyStatistics as $propertyStatistic ) {
			assert( ctype_digit( $propertyStatistic->p_id ) );
			assert( ctype_digit( $propertyStatistic->usage_count ) );

			$usageCounts[(int)$propertyStatistic->p_id] = (int)$propertyStatistic->usage_count;
		}

		return $usageCounts;
	}

	/**
	 * Deletes all rows in the table.
	 *
	 * @since 1.9
	 *
	 * @return boolean Success indicator
	 */
	public function deleteAll() {
		return $this->connection->delete(
			SQLStore::PROPERTY_STATISTICS_TABLE,
			'*',
			__METHOD__
		);
	}

	private function log( $message, $context = [] ) {

		if ( $this->logger === null ) {
			return;
		}

		$this->logger->info( $message, $context );
	}

}
