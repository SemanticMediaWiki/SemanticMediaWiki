<?php

namespace SMW\SQLStore;

use SMW\Store\PropertyStatisticsStore;
use SMW\Store;

use DatabaseBase;
use MWException;

/**
 * Simple implementation of PropertyStatisticsTable using MediaWikis
 * database abstraction layer and a single table.
 *
 * @since 1.9
 *
 * @ingroup SMWStore
 *
 * @license GNU GPL v2 or later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Nischay Nahata
 */
class PropertyStatisticsTable implements PropertyStatisticsStore {

	/** @var Store */
	protected $store = null;

	/**
	 * @since 1.9
	 * @var string
	 */
	protected $table;

	/**
	 * @since 1.9
	 * @var DatabaseBase
	 */
	protected $dbConnection;

	/**
	 * Constructor.
	 *
	 * @since 1.9
	 *
	 * @param Store $store
	 * @param string $table
	 */
	public function __construct( Store $store, $table ) {
		assert( is_string( $table ) );

		$this->store = $store;
		$this->table = $table;
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
	 * @throws MWException
	 */
	public function addToUsageCount( $propertyId, $value ) {
		if ( !is_int( $value ) ) {
			throw new MWException( 'The value to add must be an integer' );
		}

		if ( !is_int( $propertyId ) || $propertyId <= 0 ) {
			throw new MWException( 'The property id to add must be a positive integer' );
		}

		if ( $value == 0 ) {
			return true;
		}

		return $this->store->getDatabase()->update(
			$this->table,
			array(
				'usage_count = usage_count ' . ( $value > 0 ? '+ ' : '- ' ) . $this->store->getDatabase()->addQuotes( abs( $value ) ),
			),
			array(
				'p_id' => $propertyId
			),
			__METHOD__
		);
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
	 * @throws MWException
	 */
	public function setUsageCount( $propertyId, $value ) {
		if ( !is_int( $value ) || $value < 0 ) {
			throw new MWException( 'The value to add must be a positive integer' );
		}

		if ( !is_int( $propertyId ) || $propertyId <= 0 ) {
			throw new MWException( 'The property id to add must be a positive integer' );
		}

		return $this->store->getDatabase()->update(
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
	 * @throws MWException
	 */
	public function insertUsageCount( $propertyId, $value ) {
		if ( !is_int( $value ) || $value < 0 ) {
			throw new MWException( 'The value to add must be a positive integer' );
		}

		if ( !is_int( $propertyId ) || $propertyId <= 0 ) {
			throw new MWException( 'The property id to add must be a positive integer' );
		}

		return $this->store->getDatabase()->insert(
			$this->table,
			array(
				'usage_count' => $value,
				'p_id' => $propertyId,
			),
			__METHOD__
		);
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

		$propertyStatistics = $this->store->getDatabase()->select(
			$this->table,
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
		return $this->store->getDatabase()->delete(
			$this->table,
			'*',
			__METHOD__
		);
	}

}
