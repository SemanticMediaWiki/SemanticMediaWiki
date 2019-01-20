<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SQLStore\SQLStore;
use SMW\TypesRegistry;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class FixedProperties {

	use MessageReporterAwareTrait;

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var []
	 */
	private $fixedProperties = [];

	/**
	 * @var []
	 */
	private $properties = [];

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $fixedProperties
	 */
	public function setFixedProperties( array $fixedProperties = [] ) {
		$this->fixedProperties = $fixedProperties;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $properties
	 */
	public function setProperties( array $properties = [] ) {
		$this->properties = $properties;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $opts
	 */
	public function check( array $opts = [] ) {

		$this->messageReporter->reportMessage( "Checking selected fixed properties IDs ...\n" );

		if ( $this->fixedProperties === [] ) {
			$this->fixedProperties = TypesRegistry::getFixedProperties( 'id' );
		}

		if ( $this->properties === [] ) {
			$this->properties = TypesRegistry::getFixedProperties( 'id_conversion' );
		}

		foreach ( $this->properties as $prop ) {
			$this->checkAndMove( $prop );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function checkAndMove( $prop ) {

		if ( !isset( $this->fixedProperties[$prop] ) ) {
			return;
		}

		$target_id = (int)$this->fixedProperties[$prop];

		$connection = $this->store->getConnection( DB_MASTER );
		$this->messageReporter->reportMessage( "   ... reading `$prop` ...\n"  );

		$row = $connection->selectRow(
			SQLStore::ID_TABLE,
			[
				'smw_id'
			],
			[
				'smw_title' => $prop,
				'smw_namespace' => SMW_NS_PROPERTY,
				'smw_subobject' => ''
			],
			__METHOD__
		);

		if ( $row === false || (int)$row->smw_id == $target_id ) {
			return $this->messageReporter->reportMessage( "   ... done.\n"  );
		}

		$current_id = (int)$row->smw_id;

		$this->messageReporter->reportMessage( "   ... moving from $current_id to $target_id ...\n"  );

		// Avoid "Error: 1062 Duplicate entry '52' for key 'PRIMARY' ..." before moving
		$connection->delete(
			SQLStore::ID_TABLE,
			[
				'smw_id' => $target_id
			],
			__METHOD__
		);

		$this->store->getObjectIds()->moveSMWPageID(
			$current_id,
			$target_id
		);

		$connection->update(
			SQLStore::QUERY_LINKS_TABLE,
			[
				'o_id' => $target_id
			],
			[
				'o_id' => $current_id
			],
			__METHOD__
		);

		$connection->update(
			SQLStore::PROPERTY_STATISTICS_TABLE,
			[
				'p_id' => $target_id
			],
			[
				'p_id' => $current_id
			],
			__METHOD__
		);
	}

}
