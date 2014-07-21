<?php

namespace SMW\Tests;

use SMW\Tests\Util\SemanticDataValidator;

use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\ParserData;
use SMW\DIWikiPage;
use SMW\Application;

use ParserOutput;
use Title;

/**
 * @covers \SMW\ParserData
 *
 * @ingroup Test
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

		$this->assertTrue( $instance->getSemanticData()->isEmpty() );
	}

	public function testUpdateStatus() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );
		$this->assertTrue( $instance->getUpdateStatus() );

		$instance->disableBackgroundUpdateJobs();
		$this->assertFalse( $instance->getUpdateStatus() );
	}

	public function testGetterInstances() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$this->assertInstanceOf( 'Title', $instance->getTitle() );
		$this->assertInstanceOf( 'ParserOutput', $instance->getOutput() );
		$this->assertInstanceOf( '\SMW\DIWikiPage', $instance->getSubject() );
	}

	public function testAddDataVlaueAndClear() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$this->assertTrue( $instance->getSemanticData()->isEmpty() );

		$instance->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' )
		);

		$this->assertFalse( $instance->getSemanticData()->isEmpty() );
		$instance->clearData();

		$this->assertTrue( $instance->getSemanticData()->isEmpty() );
	}

	public function testAddDataValueAndUpdateOutput() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$instance->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' )
		);

		$this->assertFalse( $instance->getSemanticData()->isEmpty() );
		$instance->updateOutput();

		$title = Title::newFromText( __METHOD__ .'-1' );

		$newInstance = new ParserData( $title, $instance->getOutput() );

		$this->assertTrue(
			$instance->getSemanticData()->getHash() === $newInstance->getSemanticData()->getHash(),
			'Asserts that updateOutput() yielded an update, resulting with an identical hash in both containers'
		);
	}

	public function testSetGetSemanticData() {

		$title = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = new ParserData( $title, $parserOutput );

		$this->assertTrue( $instance->getSemanticData()->isEmpty() );

		$semanticData = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$semanticData->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' )
		);

		$instance->setSemanticData( $semanticData );

		$this->assertFalse( $instance->getSemanticData()->isEmpty() );

		$this->assertTrue(
			$semanticData->getHash() === $instance->getSemanticData()->getHash()
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
			DataValueFactory::getInstance()->newPropertyValue(
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

		$semanticDataValidator = new SemanticDataValidator();

		$semanticDataValidator->assertThatPropertiesAreSet(
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

		$instance->updateOutput();

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

		Application::getInstance()->registerObject( 'Store', $store );

		$instance = new ParserData(
			Title::newFromText( __METHOD__ ),
			new ParserOutput()
		);

		$this->assertTrue( $instance->updateStore() );

		Application::clear();
	}

}
