<?php

namespace SMW\Tests\SQLStore;

use SMW\Tests\TestEnvironment;
use SMW\SQLStore\TableIntegrityExaminer;

/**
 * @covers \SMW\SQLStore\TableIntegrityExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableIntegrityExaminerTest extends \PHPUnit_Framework_TestCase {

	private $spyMessageReporter;
	private $hashField;
	private $touchedField;
	private $idBorder;
	private $store;
	private $fixedProperties;

	protected function setUp() {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->hashField = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Examiner\HashField' )
			->disableOriginalConstructor()
			->getMock();

		$this->fixedProperties = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Examiner\FixedProperties' )
			->disableOriginalConstructor()
			->getMock();

		$this->touchedField = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Examiner\TouchedField' )
			->disableOriginalConstructor()
			->getMock();

		$this->idBorder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Examiner\IdBorder' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			TableIntegrityExaminer::class,
			new TableIntegrityExaminer( $this->store, $this->hashField, $this->fixedProperties, $this->touchedField, $this->idBorder )
		);
	}

	public function testCheckOnPostCreationOnValidProperty() {

		$row = [
			'smw_id' => 42,
			'smw_iw' => '',
			'smw_proptable_hash' => '',
			'smw_hash' => ''
		];

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getPropertyInterwiki', 'moveSMWPageID', 'getPropertyTableHashes' ] )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getPropertyInterwiki' )
			->will( $this->returnValue( 'Foo' ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'replace' );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder->expects( $this->once() )
			->method( 'checkOn' );

		$instance = new TableIntegrityExaminer(
			$store,
			$this->hashField,
			$this->fixedProperties,
			$this->touchedField,
			$this->idBorder
		);

		$instance->setPredefinedPropertyList( [
			'Foo' => 42
		] );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostCreation( $tableBuilder );
	}

	public function testCheckOnPostCreationOnValidProperty_NotFixed() {

		$row = [
			'smw_id' => 42,
			'smw_iw' => '',
			'smw_proptable_hash' => '',
			'smw_hash' => ''
		];

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'moveSMWPageID', 'getPropertyInterwiki' ] )
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->at( 0 ) )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [
					'smw_title' => 'Foo',
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_subobject' => '' ] ) )
			->will( $this->returnValue( (object)$row ) );

		$connection->expects( $this->at( 1 ) )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder->expects( $this->once() )
			->method( 'checkOn' );

		$instance = new TableIntegrityExaminer(
			$store,
			$this->hashField,
			$this->fixedProperties,
			$this->touchedField,
			$this->idBorder
		);

		$instance->setPredefinedPropertyList( [
			'Foo' => null
		] );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostCreation( $tableBuilder );
	}

	public function testCheckOnPostCreationOnInvalidProperty() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'getPropertyInterwiki', 'moveSMWPageID' ] )
			->getMock();

		$idTable->expects( $this->never() )
			->method( 'getPropertyInterwiki' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder->expects( $this->once() )
			->method( 'checkOn' );

		$instance = new TableIntegrityExaminer(
			$store,
			$this->hashField,
			$this->fixedProperties,
			$this->touchedField,
			$this->idBorder
		);

		$instance->setPredefinedPropertyList( [
			'_FOO' => 42
		] );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostCreation( $tableBuilder );

		$this->assertContains(
			'invalid registration',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testCheckOnActivitiesPostCreationForID_TABLE() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'tableName' )
			->will( $this->returnValue( 'smw_object_ids' ) );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'moveSMWPageID' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder->expects( $this->any() )
			->method( 'getLog' )
			->will( $this->returnValue( [ 'smw_object_ids' => [ 'smw_sort' => 'field.new' ] ] ) );

		$tableBuilder->expects( $this->once() )
			->method( 'checkOn' );

		$instance = new TableIntegrityExaminer(
			$store,
			$this->hashField,
			$this->fixedProperties,
			$this->touchedField,
			$this->idBorder
		);

		$instance->setPredefinedPropertyList( [] );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostCreation( $tableBuilder );

		$this->assertContains(
			'copying smw_sortkey to smw_sort',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testCheckOnPostDestruction() {

		$connection = $this->getMockBuilder( '\DatabaseBase' )
			->disableOriginalConstructor()
			->setMethods( [ 'listTables' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'listTables' )
			->will( $this->returnValue( [ 'abcsmw_foo' ] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder->expects( $this->once() )
			->method( 'checkOn' );

		$tableBuilder->expects( $this->once() )
			->method( 'drop' );

		$instance = new TableIntegrityExaminer(
			$store,
			$this->hashField,
			$this->fixedProperties,
			$this->touchedField,
			$this->idBorder
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostDestruction( $tableBuilder );
	}

}
