<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\TableIntegrityExaminer;
use Onoi\MessageReporter\MessageReporterFactory;

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

		$row = new \stdClass;
		$row->smw_id = 42;

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getPropertyInterwiki', 'moveSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getPropertyInterwiki' )
			->will( $this->returnValue( 'Foo' ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->will( $this->returnValue( $row ) );

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

		$instance->setPredefinedProperties( array(
			'Foo' => 42
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

		$instance->setPredefinedProperties( array(
			'_FOO' => 42
		) );

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostCreation( $tableBuilder );

		$this->assertContains(
			'skipping',
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
