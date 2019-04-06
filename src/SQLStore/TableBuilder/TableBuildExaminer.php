<?php

namespace SMW\SQLStore\TableBuilder;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use Onoi\MessageReporter\NullMessageReporter;
use SMW\DIProperty;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\MediaWiki\Collator;
use SMW\PropertyRegistry;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\Installer;
use SMW\SQLStore\TableBuilder as ITableBuilder;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\SQLStore\TableBuilder\Examiner\FixedProperties;
use SMW\SQLStore\TableBuilder\Examiner\TouchedField;
use SMW\SQLStore\TableBuilder\Examiner\IdBorder;
use SMWSql3SmwIds;

/**
 * @private
 *
 * Allows to execute SQLStore or table specific examination tasks that are
 * expected to be part of the installation or removal routine.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableBuildExaminer {

	use MessageReporterAwareTrait;

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var TableBuildExaminerFactory
	 */
	private $tableBuildExaminerFactory;

	/**
	 * @var array
	 */
	private $predefinedPropertyList = [];

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 * @param TableBuildExaminerFactory $tableBuildExaminerFactory
	 */
	public function __construct( SQLStore $store, TableBuildExaminerFactory $tableBuildExaminerFactory ) {
		$this->store = $store;
		$this->tableBuildExaminerFactory = $tableBuildExaminerFactory;
		$this->messageReporter = new NullMessageReporter();
		$this->setPredefinedPropertyList( PropertyRegistry::getInstance()->getPropertyList() );
	}

	/**
	 * @since 2.5
	 *
	 * @param array $propertyList
	 */
	public function setPredefinedPropertyList( array $propertyList ) {

		$fixedPropertyList = SMWSql3SmwIds::$special_ids;
		$predefinedPropertyList = [];

		foreach ( $propertyList as $key => $val ) {
			$predefinedPropertyList[$key] = null;

			if ( isset( $fixedPropertyList[$key] ) && is_int( $fixedPropertyList[$key] ) ) {
				$predefinedPropertyList[$key] = $fixedPropertyList[$key];
			} elseif ( is_int( $val ) ) {
				$predefinedPropertyList[$key] = $val;
			}
		}

		$this->predefinedPropertyList = $predefinedPropertyList;
	}

	/**
	 * @since 2.5
	 *
	 * @param TableBuilder $tableBuilder
	 */
	public function checkOnPostCreation( ITableBuilder $tableBuilder ) {

		$fixedProperties = $this->tableBuildExaminerFactory->newFixedProperties(
			$this->store
		);

		$fixedProperties->setMessageReporter( $this->messageReporter );
		$fixedProperties->check();

		$idBorder = $this->tableBuildExaminerFactory->newIdBorder(
			$this->store
		);

		$idBorder->setMessageReporter( $this->messageReporter );

		$idBorder->check(
			[
				// #3314 (3.0-)
				$idBorder::LEGACY_BOUND => 50,
				$idBorder::UPPER_BOUND  => SQLStore::FIXED_PROPERTY_ID_UPPERBOUND
			]
		);

		$predefinedProperties = $this->tableBuildExaminerFactory->newPredefinedProperties(
			$this->store
		);

		$predefinedProperties->setMessageReporter( $this->messageReporter );

		$predefinedProperties->setPredefinedPropertyList(
			$this->predefinedPropertyList
		);

		$predefinedProperties->check();

		$hashField = $this->tableBuildExaminerFactory->newHashField(
			$this->store
		);

		$hashField->setMessageReporter( $this->messageReporter );
		$hashField->check();

		$this->checkSortField( $tableBuilder->getLog() );

		$touchedField = $this->tableBuildExaminerFactory->newTouchedField(
			$this->store
		);

		$touchedField->setMessageReporter( $this->messageReporter );
		$touchedField->check();

		// Call out for RDBMS specific implementations
		$tableBuilder->checkOn( TableBuilder::POST_CREATION );
	}

	/**
	 * @since 2.5
	 *
	 * @param TableBuilder $tableBuilder
	 */
	public function checkOnPostDestruction( ITableBuilder $tableBuilder ) {

		$connection = $this->store->getConnection( DB_MASTER );

		// Find orphaned tables that have not been removed but were produced and
		// handled by SMW
		foreach ( $connection->listTables() as $table ) {
			if ( strpos( $table, TableBuilder::TABLE_PREFIX ) !== false ) {

				// Remove any MW specific prefix at this point which will be
				// handled by the DB class (abcsmw_foo -> smw_foo)
				$tableBuilder->drop( new Table( strstr( $table, TableBuilder::TABLE_PREFIX ) ) );
			}
		}

		// Call out for RDBMS specific implementations
		$tableBuilder->checkOn( TableBuilder::POST_DESTRUCTION );
	}

	private function checkSortField( $log ) {

		$connection = $this->store->getConnection( DB_MASTER );

		$tableName = $connection->tableName( SQLStore::ID_TABLE );
		$this->messageReporter->reportMessage( "Checking smw_sortkey, smw_sort fields ...\n" );

		// #2429, copy smw_sortkey content to the new smw_sort field once
		if ( isset( $log[$tableName]['smw_sort'] ) && $log[$tableName]['smw_sort'] === TableBuilder::PROC_FIELD_NEW ) {
			$emptyField = 'smw_sort';
			$copyField = 'smw_sortkey';

			$this->messageReporter->reportMessage( "   Table " . SQLStore::ID_TABLE . " ...\n" );
			$this->messageReporter->reportMessage( "   ... copying $copyField to $emptyField ... " );
			$connection->query( "UPDATE $tableName SET $emptyField = $copyField", __METHOD__ );
			$this->messageReporter->reportMessage( "done.\n" );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

}
