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

	public function testCheckOnPostCreation() {

		$row = new \stdClass;
		$row->smw_id = 42;

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'getPropertyInterwiki', 'moveSMWPageID' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getPropertyInterwiki' )
			->will( $this->returnValue( 'Foo' ) );

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

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostCreation( $tableBuilder );
	}

}
