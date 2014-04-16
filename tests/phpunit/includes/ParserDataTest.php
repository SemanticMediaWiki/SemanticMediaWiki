<?php

namespace SMW\Test;

use SMW\Tests\Util\SemanticDataValidator;
use SMW\Tests\Util\Mock\MockUpdateObserver;

use SMW\ObservableSubjectDispatcher;
use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\ParserData;
use SMW\DIWikiPage;

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
 * @licence GNU GPL v2+
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
		$instance = $this->acquireInstance();
		$this->assertTrue( $instance->getSemanticData()->isEmpty() );
	}

	public function testUpdateStatus() {

		$instance = $this->acquireInstance();
		$this->assertTrue( $instance->getUpdateStatus() );

		$instance->disableUpdateJobs();
		$this->assertFalse( $instance->getUpdateStatus() );
	}

	public function testGetterInstances() {

		$instance = $this->acquireInstance();

		$this->assertInstanceOf( 'Title', $instance->getTitle() );
		$this->assertInstanceOf( 'ParserOutput', $instance->getOutput() );
		$this->assertInstanceOf( '\SMW\DIWikiPage', $instance->getSubject() );
	}

	public function testAddDataVlaueAndClear() {

		$instance = $this->acquireInstance();

		$this->assertTrue(
			$instance->getSemanticData()->isEmpty(),
			'Asserts that the initial container is empty'
		);

		$instance->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' )
		);

		$this->assertFalse(
			$instance->getSemanticData()->isEmpty(),
			'Asserts that the container is longer empty'
		);

		$instance->clearData();

		$this->assertTrue(
			$instance->getSemanticData()->isEmpty(),
			'Asserts that clearData() yields an empty container'
		);
	}

	public function testAddDataValueAndUpdateOutput() {

		$instance = $this->acquireInstance();

		$instance->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' )
		);

		$this->assertFalse(
			$instance->getSemanticData()->isEmpty(),
			'Asserts that the container is no longer empty'
		);

		$instance->updateOutput();

		$acquireInstance = $this->acquireInstance( null, $instance->getOutput() );

		$this->assertTrue(
			$instance->getSemanticData()->getHash() === $acquireInstance->getSemanticData()->getHash(),
			'Asserts that updateOutput() yielded an update, resulting with an identical hash in both containers'
		);
	}

	public function testSetGetSemanticData() {

		$instance = $this->acquireInstance();

		$this->assertTrue(
			$instance->getSemanticData()->isEmpty(),
			'Asserts that the container is empty'
		);

		$semanticData = new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) );

		$semanticData->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' )
		);

		$instance->setSemanticData( $semanticData );

		$this->assertFalse(
			$instance->getSemanticData()->isEmpty(),
			'Asserts that the container is no longer empty'
		);

		$this->assertTrue(
			$semanticData->getHash() === $instance->getSemanticData()->getHash(),
			'Asserts that both containers are identical'
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

		$instance = $this->acquireInstance();

		$instance->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue(
				$propertyName,
				$value
			)
		);

		if ( $errorCount === 0 ){
			$expected['propertyCount']  = $propertyCount;
			$expected['propertyLabels'] = $propertyName;
			$expected['propertyValues'] = $value;
			$this->assertInstanceOf( '\SMW\SemanticData', $instance->getSemanticData() );

			$semanticDataValidator = new SemanticDataValidator;

			$semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$instance->getSemanticData()
			);

		} else {
			$this->assertCount( $errorCount, $instance->getErrors() );
		}
	}

	public function testUpdateStore() {

		$notifier     = 'runStoreUpdater';
		$title        = Title::newFromText( __METHOD__ );
		$parserOutput = new ParserOutput();

		$instance = $this->acquireInstance( $title, $parserOutput );
		$observer = new MockUpdateObserver();

		$instance->registerDispatcher( new ObservableSubjectDispatcher( $observer ) );

		$this->assertTrue( $instance->updateStore() );

		$this->assertEquals(
			$notifier,
			$observer->getNotifier(),
			'Asserts that the Observer was notified'
		);
	}

	/**
	 * @return ParserData
	 */
	private function acquireInstance( Title $title = null, ParserOutput $parserOutput = null ) {

		if ( $title === null ) {
			$title = Title::newFromText( __METHOD__ );
		}

		if ( $parserOutput === null ) {
			$parserOutput = new ParserOutput();
		}

		return new ParserData( $title, $parserOutput );
	}

}
