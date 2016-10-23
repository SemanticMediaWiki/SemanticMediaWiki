<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\TableSchemaManager;
use Onoi\MessageReporter\MessageReporterFactory;

/**
 * @covers \SMW\SQLStore\TableSchemaManager
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableSchemaManagerTest extends \PHPUnit_Framework_TestCase {

	private $spyMessageReporter;

	protected function setUp() {
		parent::setUp();
		$this->spyMessageReporter = MessageReporterFactory::getInstance()->newSpyMessageReporter();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableIntegrityChecker = $this->getMockBuilder( '\SMW\SQLStore\TableIntegrityChecker' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableSchemaManager',
			new TableSchemaManager( $store, $tableBuilder, $tableIntegrityChecker )
		);
	}

	public function testCreate() {

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $propertyTableDefinition ) ) );

		$store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableIntegrityChecker = $this->getMockBuilder( '\SMW\SQLStore\TableIntegrityChecker' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableSchemaManager(
			$store,
			$tableBuilder,
			$tableIntegrityChecker
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->create();
	}

	public function testDrop() {

		$propertyTableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$dataItemHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->once() )
			->method( 'getTableFields' )
			->will( $this->returnValue( array() ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $propertyTableDefinition ) ) );

		$store->expects( $this->once() )
			->method( 'getDataItemHandlerForDIType' )
			->will( $this->returnValue( $dataItemHandler ) );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableIntegrityChecker = $this->getMockBuilder( '\SMW\SQLStore\TableIntegrityChecker' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TableSchemaManager(
			$store,
			$tableBuilder,
			$tableIntegrityChecker
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->drop();
	}

}
