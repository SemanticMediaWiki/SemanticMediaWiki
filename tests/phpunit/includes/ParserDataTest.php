<?php

namespace SMW\Tests;

use SMW\Tests\Utils\UtilityFactory;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\ParserData;
use SMW\DIWikiPage;

use ParserOutput;
use Title;

/**
 * @covers \SMW\ParserData
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ParserDataTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $dataValueFactory;

	protected function setUp() {
		parent::setUp();

		$this->semanticDataValidator = UtilityFactory::getInstance()->newValidatorFactory()->newSemanticDataValidator();
		$this->dataValueFactory = DataValueFactory::getInstance();
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
			$instance->getUpdateJobState()
		);

		$instance->disableBackgroundUpdateJobs();

		$this->assertFalse(
			$instance->getUpdateJobState()
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
			$this->dataValueFactory->newPropertyValue( 'Foo', 'Bar' )
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
			$this->dataValueFactory->newPropertyValue( 'Foo', 'Bar' )
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
			$this->dataValueFactory->newPropertyValue( 'Foo', 'Bar' )
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
			$this->dataValueFactory->newPropertyValue(
				$propertyName,
				$value
			)
		);

		if ( $errorCount > 0 ) {
			return $this->assertCount( $errorCount, $instance->getErrors() );
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

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'updateData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'updateData' );

		ApplicationFactory::getInstance()->registerObject( 'Store', $store );

		$instance = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$this->assertTrue( $instance->updateStore() );

		ApplicationFactory::clear();
	}

	public function testSemanticDataStateToParserOutput() {

		$parserOutput = new ParserOutput();

		$instance = new ParserData(
			Title::newFromText( __METHOD__ ),
			$parserOutput
		);

		$this->assertEmpty(
			$parserOutput->getProperty( 'smw-semanticdata-status' )
		);

		$instance->addDataValue(
			$this->dataValueFactory->newPropertyValue(
				'Foo',
				'Bar'
			)
		);

		$instance->setSemanticDataStateToParserOutputProperty();

		$this->assertTrue(
			$parserOutput->getProperty( 'smw-semanticdata-status' )
		);
	}

	public function testImportFromParserOutput() {

		$import = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$import->addDataValue(
			$this->dataValueFactory->newPropertyValue(
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

}
