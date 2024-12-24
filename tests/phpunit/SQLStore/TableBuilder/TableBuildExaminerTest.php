<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\Tests\TestEnvironment;
use SMW\SQLStore\TableBuilder\TableBuildExaminer;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\TableBuilder\TableBuildExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TableBuildExaminerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $spyMessageReporter;
	private $hashField;
	private $touchedField;
	private $idBorder;
	private $predefinedProperties;
	private $store;
	private $fixedProperties;
	private $tableBuildExaminerFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

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

		$this->predefinedProperties = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\Examiner\PredefinedProperties' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuildExaminerFactory = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder\TableBuildExaminerFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->tableBuildExaminerFactory->expects( $this->any() )
			->method( 'newPredefinedProperties' )
			->willReturn( $this->predefinedProperties );

		$this->tableBuildExaminerFactory->expects( $this->any() )
			->method( 'newIdBorder' )
			->willReturn( $this->idBorder );

		$this->tableBuildExaminerFactory->expects( $this->any() )
			->method( 'newTouchedField' )
			->willReturn( $this->touchedField );

		$this->tableBuildExaminerFactory->expects( $this->any() )
			->method( 'newFixedProperties' )
			->willReturn( $this->fixedProperties );

		$this->tableBuildExaminerFactory->expects( $this->any() )
			->method( 'newHashField' )
			->willReturn( $this->hashField );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TableBuildExaminer::class,
			new TableBuildExaminer( $this->store, $this->tableBuildExaminerFactory )
		);
	}

	public function testCheckOnActivitiesPostCreationForID_TABLE() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$connection->expects( $this->atLeastOnce() )
			->method( 'tableName' )
			->willReturn( 'smw_object_ids' );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->onlyMethods( [ 'moveSMWPageID' ] )
			->getMock();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder->expects( $this->any() )
			->method( 'getLog' )
			->willReturn( [ 'smw_object_ids' => [ 'smw_sort' => 'field.new' ] ] );

		$tableBuilder->expects( $this->once() )
			->method( 'checkOn' );

		$instance = new TableBuildExaminer(
			$store,
			$this->tableBuildExaminerFactory
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
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'listTables' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->atLeastOnce() )
			->method( 'listTables' )
			->willReturn( [ 'abcsmw_foo' ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$tableBuilder = $this->getMockBuilder( '\SMW\SQLStore\TableBuilder' )
			->disableOriginalConstructor()
			->getMock();

		$tableBuilder->expects( $this->once() )
			->method( 'checkOn' );

		$tableBuilder->expects( $this->once() )
			->method( 'drop' );

		$instance = new TableBuildExaminer(
			$store,
			$this->tableBuildExaminerFactory
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->checkOnPostDestruction( $tableBuilder );
	}

	public function testGetDatabaseInfo() {
		$connection = $this->getMockBuilder( '\Wikimedia\Rdbms\Database' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getType', 'getServerInfo' ] )
			->getMockForAbstractClass();

		$connection->expects( $this->once() )
			->method( 'getType' )
			->willReturn( 'foo' );

		$connection->expects( $this->once() )
			->method( 'getServerInfo' )
			->willReturn( 2 );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getConnection' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TableBuildExaminer(
			$store,
			$this->tableBuildExaminerFactory
		);

		$this->assertEquals(
			'foo (2)',
			$instance->getDatabaseInfo()
		);
	}

}
