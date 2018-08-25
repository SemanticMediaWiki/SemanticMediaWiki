<?php

namespace SMW\Tests\SQLStore;

use Onoi\MessageReporter\MessageReporterFactory;
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

	protected function setUp() {
		parent::setUp();
		$this->spyMessageReporter = MessageReporterFactory::getInstance()->newSpyMessageReporter();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableIntegrityExaminer',
			new TableIntegrityExaminer( $store )
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
			->setMethods( array( 'getPropertyInterwiki', 'moveSMWPageID', 'getPropertyTableHashes' ) )
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
			->setMethods( array( 'getObjectIds', 'getConnection' ) )
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
			$store
		);

		$instance->setPredefinedPropertyList( array(
			'Foo' => 42
		) );

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
			->setMethods( array( 'moveSMWPageID', 'getPropertyInterwiki' ) )
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->at( 1 ) )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)[ 'smw_id' => \SMW\SQLStore\SQLStore::FIXED_PROPERTY_ID_UPPERBOUND ] ) );

		$connection->expects( $this->at( 2 ) )
			->method( 'selectRow' )
			->with(
				$this->anything(),
				$this->anything(),
				$this->equalTo( [
					'smw_title' => 'Foo',
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_subobject' => '' ] ) )
			->will( $this->returnValue( (object)$row ) );

		$connection->expects( $this->at( 3 ) )
			->method( 'selectRow' )
			->will( $this->returnValue( (object)$row ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds', 'getConnection' ) )
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
			$store
		);

		$instance->setPredefinedPropertyList( array(
			'Foo' => null
		) );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostCreation( $tableBuilder );
	}

	public function testCheckOnPostCreationOnInvalidProperty() {

		$row = new \stdClass;
		$row->smw_id = 42;

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getPropertyInterwiki', 'moveSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->never() )
			->method( 'getPropertyInterwiki' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->will( $this->returnValue( $row ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getObjectIds', 'getConnection' ) )
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
			$store
		);

		$instance->setPredefinedPropertyList( array(
			'_FOO' => 42
		) );

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
			->setMethods( array( 'moveSMWPageID' ) )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection', 'getObjectIds' ) )
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
			->will( $this->returnValue( array( 'smw_object_ids' => array( 'smw_sort' => 'field.new' ) ) ) );

		$tableBuilder->expects( $this->once() )
			->method( 'checkOn' );

		$instance = new TableIntegrityExaminer(
			$store
		);

		$instance->setPredefinedPropertyList( array() );

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
			->setMethods( array( 'listTables' ) )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'listTables' )
			->will( $this->returnValue( array( 'abcsmw_foo' ) ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getConnection' ) )
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
			$store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostDestruction( $tableBuilder );
	}

}
