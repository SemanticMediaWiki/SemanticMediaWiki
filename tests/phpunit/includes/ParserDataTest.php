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

		$this->assertTrue(
			$instance->isEnabledWithUpdateJob()
		);

		$instance->disableBackgroundUpdateJobs();

		$this->assertFalse(
			$instance->isEnabledWithUpdateJob()
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
		return array(
			array( 'Foo'  , 'Bar', 0, 1 ),
			array( '-Foo' , 'Bar', 1, 0 ),
			array( '_Foo' , 'Bar', 1, 0 ),
		);
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

		$expected = array(
			'propertyCount'  => $propertyCount,
			'propertyLabels' => $propertyName,
			'propertyValues' => $value
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$instance->getSemanticData()
		);
	}

	public function testSetGetForNonExtensionDataLegacyAccess() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = $this->getMockBuilder( '\SMW\ParserData' )
			->setConstructorArgs( array( $title, $parserOutput ) )
			->setMethods( array( 'hasExtensionData' ) )
			->getMock();

		$instance->expects( $this->any() )
			->method( 'hasExtensionData' )
			->will( $this->returnValue( false ) );

		$instance->pushSemanticDataToParserOutput();

		$this->assertInstanceOf(
			'\SMW\SemanticData',
			$instance->getSemanticData()
		);
	}

	public function testUpdateStore() {

		$idTable = $this->getMockBuilder( '\stdClass' )
			->setMethods( array( 'exists' ) )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'clearData', 'getObjectIds' ) )
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

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getLatestRevID' )
			->will( $this->returnValue( 42 ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'fetch' )
			->with( $this->stringContains( ':smw:update:55fd50809b6221a77f8f3dbd49e0d5bc' ) )
			->will( $this->returnValue( 42 ) );

		$cache->expects( $this->once() )
			->method( 'save' )
			->with( $this->stringContains( ':smw:update:55fd50809b6221a77f8f3dbd49e0d5bc' ) );

		$instance = new ParserData(
			$title,
			new ParserOutput(),
			$cache
		);

		$instance->markUpdate( 42 );

		$this->assertNull(
			$instance->updateStore()
		);
	}

	public function testSemanticDataStateToParserOutput() {

		$parserOutput = new ParserOutput();

		$instance = new ParserData(
			Title::newFromText( __METHOD__ ),
			$parserOutput
		);

		$this->assertFalse(
			$instance->isAnnotatedWithSemanticData()
		);

		$instance->addDataValue(
			$this->dataValueFactory->newDataValueByText(
				'Foo',
				'Bar'
			)
		);

		$instance->setSemanticDataStateToParserOutputProperty();

		$this->assertTrue(
			$instance->isAnnotatedWithSemanticData()
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
			->setMethods( array( 'setLimitReportData' ) )
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

	public function testCanModifySemanticData() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->will( $this->returnValue( -1 ) );

		$parserOutput = new ParserOutput();

		// FIXME 1.21+
		if ( !method_exists( $parserOutput, 'getExtensionData' ) ) {
			$this->markTestSkipped( 'getExtensionData is not available.' );
		}

		$instance = new ParserData(
			$title,
			$parserOutput
		);

		$this->assertTrue(
			$instance->canModifySemanticData()
		);

		$parserOutput->setExtensionData( 'smw-blockannotation', true );

		$this->assertFalse(
			$instance->canModifySemanticData()
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

}
