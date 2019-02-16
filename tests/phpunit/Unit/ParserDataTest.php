<?php

namespace SMW\Tests;

use ParserOutput;
use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\ParserData;
use SMW\SemanticData;
use Title;

/**
 * @covers \SMW\ParserData
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserDataTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $dataValueFactory;
	private $testEnvironment;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( -1 ) );

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ParserData',
			new ParserData( $title, $parserOutput )
		);
	}

	public function testInitialDataIsEmpty() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$this->assertTrue(
			$instance->getSemanticData()->isEmpty()
		);
	}

	public function testUpdateJobState() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$instance->setOption( ParserData::OPT_CREATE_UPDATE_JOB, false );

		$this->assertFalse(
			$instance->getOption( ParserData::OPT_CREATE_UPDATE_JOB )
		);
	}

	public function testGetterInstances() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$this->assertInstanceOf(
			'Title',
			$instance->getTitle()
		);

		$this->assertInstanceOf(
			'ParserOutput',
			$instance->getOutput()
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getSubject()
		);
	}

	public function testAddDataVlaueAndClear() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$this->assertTrue(
			$instance->getSemanticData()->isEmpty()
		);

		$instance->addDataValue(
			$this->dataValueFactory->newDataValueByText( 'Foo', 'Bar' )
		);

		$this->assertFalse(
			$instance->getSemanticData()->isEmpty()
		);

		$instance->setEmptySemanticData();

		$this->assertTrue(
			$instance->getSemanticData()->isEmpty()
		);
	}

	public function testAddDataValueAndPushSemanticDataToParserOutput() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$instance->addDataValue(
			$this->dataValueFactory->newDataValueByText( 'Foo', 'Bar' )
		);

		$this->assertFalse( $instance->getSemanticData()->isEmpty() );
		$instance->pushSemanticDataToParserOutput();

		$title = Title::newFromText( __METHOD__ .'-1' );

		$newInstance = new ParserData( $title, $instance->getOutput() );

		$this->assertEquals(
			$instance->getSemanticData()->getHash(),
			$newInstance->getSemanticData()->getHash()
		);
	}

	public function testSetGetSemanticData() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$this->assertTrue( $instance->getSemanticData()->isEmpty() );

		$semanticData = new SemanticData(
			DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) )
		);

		$semanticData->addDataValue(
			$this->dataValueFactory->newDataValueByText( 'Foo', 'Bar' )
		);

		$instance->setSemanticData( $semanticData );

		$this->assertFalse( $instance->getSemanticData()->isEmpty() );

		$this->assertEquals(
			$semanticData->getHash(),
			$instance->getSemanticData()->getHash()
		);
	}

	public function getPropertyValueDataProvider() {
		return [
			[ 'Foo'  , 'Bar', 0, 1 ],
			[ '-Foo' , 'Bar', 1, 0 ],
			[ '_Foo' , 'Bar', 1, 0 ],
		];
	}

	/**
	 * @dataProvider getPropertyValueDataProvider
	 */
	public function testAddDataValue( $propertyName, $value, $errorCount, $propertyCount ) {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$instance->addDataValue(
			$this->dataValueFactory->newDataValueByText(
				$propertyName,
				$value
			)
		);

		if ( $errorCount > 0 ) {
			return $this->assertCount( $errorCount, $instance->getSemanticData()->getErrors() );
		}

		$expected = [
			'propertyCount'  => $propertyCount,
			'propertyLabels' => $propertyName,
			'propertyValues' => $value
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testUpdateStore() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'exists', 'findAssociatedRev' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$idTable->expects( $this->any() )
			->method( 'findAssociatedRev' )
			->will( $this->returnValue( 42 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'clearData', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->once() )
			->method( 'clearData' );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$this->assertTrue(
			$instance->updateStore()
		);
	}

	public function testSkipUpdateOnMatchedMarker() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( [ 'findAssociatedRev' ] )
			->getMock();

		$idTable->expects( $this->once() )
			->method( 'findAssociatedRev' )
			->will( $this->returnValue( 42 ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ParserData(
			$title,
			new ParserOutput()
		);

		$instance->setLogger( $logger );

		$this->assertFalse(
			$instance->updateStore()
		);
	}

	public function testHasSemanticData() {

		$parserOutput = new ParserOutput();

		$instance = new ParserData(
			Title::newFromText( __METHOD__ ),
			$parserOutput
		);

		$this->assertFalse(
			$instance->hasSemanticData( $parserOutput )
		);

		$instance->addDataValue(
			$this->dataValueFactory->newDataValueByText(
				'Foo',
				'Bar'
			)
		);

		$instance->markParserOutput();

		$this->assertTrue(
			$instance->hasSemanticData( $parserOutput )
		);
	}

	public function testImportFromParserOutput() {

		$import = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$import->addDataValue(
			$this->dataValueFactory->newDataValueByText(
				'Foo',
				'Bar'
			)
		);

		$import->pushSemanticDataToParserOutput();

		$instance = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$instance->importFromParserOutput( null );

		$this->assertNotEquals(
			$import->getSemanticData()->getHash(),
			$instance->getSemanticData()->getHash()
		);

		$instance->importFromParserOutput( $import->getOutput() );

		$this->assertEquals(
			$import->getSemanticData()->getHash(),
			$instance->getSemanticData()->getHash()
		);
	}

	public function testAddLimitReport() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( -1 ) );

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->setMethods( [ 'setLimitReportData' ] )
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'setLimitReportData' )
			->with(
				$this->stringContains( 'smw-limitreport-Foo' ),
				$this->stringContains( 'Bar' ) );

		// FIXME 1.22+
		if ( !method_exists( $parserOutput, 'setLimitReportData' ) ) {
			$this->markTestSkipped( 'LimitReportData is not available.' );
		}

		$instance = new ParserData( $title, $parserOutput );
		$instance->addLimitReport( 'Foo', 'Bar' );
	}

	public function testIsBlocked() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( -1 ) );

		$parserOutput = new ParserOutput();

		$instance = new ParserData(
			$title,
			$parserOutput
		);

		$this->assertFalse(
			$instance->isBlocked()
		);

		$parserOutput->setExtensionData( ParserData::ANNOTATION_BLOCK, true );

		$this->assertTrue(
			$instance->isBlocked()
		);
	}

	public function testSetGetOption() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( -1 ) );

		$parserOutput = new ParserOutput();

		$instance = new ParserData(
			$title,
			$parserOutput
		);

		$instance->setOption( $instance::NO_QUERY_DEPENDENCY_TRACE, true );

		$this->assertTrue(
			$instance->getOption( $instance::NO_QUERY_DEPENDENCY_TRACE )
		);
	}

	public function testAddExtraParserKey() {

		$parserOptions = $this->getMockBuilder( '\ParserOptions' )
			->disableOriginalConstructor()
			->getMock();

		$parserOptions->expects( $this->once() )
			->method( 'addExtraKey' )
			->with( $this->stringContains( 'Foo' ) );

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( -1 ) );

		$parserOutput = $this->getMockBuilder( 'ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$parserOutput->expects( $this->once() )
			->method( 'recordOption' )
			->with( $this->stringContains( 'userlang' ) );

		$instance = new ParserData(
			$title,
			$parserOutput
		);

		$instance->setParserOptions( $parserOptions );
		$instance->addExtraParserKey( 'Foo' );
		$instance->addExtraParserKey( 'userlang' );
	}

}
