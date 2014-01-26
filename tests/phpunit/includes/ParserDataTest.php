<?php

namespace SMW\Test;

use SMW\ObservableSubjectDispatcher;
use SMW\DataValueFactory;
use SMw\SemanticData;
use SMW\ParserData;

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
class ParserDataTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ParserData';
	}

	/**
	 * @since  1.9
	 *
	 * @return ParserData
	 */
	private function newInstance( Title $title = null, ParserOutput $parserOutput = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		if ( $parserOutput === null ) {
			$parserOutput = $this->newParserOutput();
		}

		return new ParserData( $title, $parserOutput );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testInitialDataIsEmpty() {
		$this->assertTrue( $this->newInstance()->getData()->isEmpty() );
	}

	/**
	 * @since 1.9
	 */
	public function testUpdateStatus() {

		$instance = $this->newInstance();

		$this->assertTrue( $instance->getUpdateStatus() );

		$instance->disableUpdateJobs();

		$this->assertFalse( $instance->getUpdateStatus() );

	}

	/**
	 * @since 1.9
	 */
	public function testGetTitleAndDIWikiPageAndParserOutput() {

		$instance = $this->newInstance();

		$this->assertInstanceOf( 'Title', $instance->getTitle() );
		$this->assertInstanceOf( 'ParserOutput', $instance->getOutput() );
		$this->assertInstanceOf( '\SMW\DIWikiPage', $instance->getSubject() );

	}

	/**
	 * @since 1.9
	 */
	public function testAddAndClearData() {

		$instance = $this->newInstance();

		$this->assertTrue(
			$instance->getData()->isEmpty(),
			'Asserts that the initial container is empty'
		);

		$instance->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$this->assertFalse(
			$instance->getData()->isEmpty(),
			'Asserts that the container is longer empty'
		);

		$instance->clearData();

		$this->assertTrue(
			$instance->getData()->isEmpty(),
			'Asserts that clearData() yields an empty container'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testAddAndUpdateOutput() {

		$instance = $this->newInstance();

		$instance->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$this->assertFalse(
			$instance->getData()->isEmpty(),
			'Asserts that the container is no longer empty'
		);

		$instance->updateOutput();

		$newInstance = $this->newInstance( null, $instance->getOutput() );

		$this->assertTrue(
			$instance->getData()->getHash() === $newInstance->getData()->getHash(),
			'Asserts that updateOutput() yielded an update, resulting with an identical hash in both containers'
		);

	}


	/**
	 * @since 1.9
	 */
	public function testSetGetData() {

		$instance = $this->newInstance();

		$this->assertTrue(
			$instance->getData()->isEmpty(),
			'Asserts that the container is empty'
		);

		$data = new SemanticData( $this->newSubject() );
		$data->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$instance->setData( $data );

		$this->assertFalse(
			$instance->getData()->isEmpty(),
			'Asserts that the container is no longer empty'
		);

		$this->assertTrue(
			$data->getHash() === $instance->getData()->getHash(),
			'Asserts that both containers are identical'
		);

	}

	/**
	 * @return array
	 */
	public function getPropertyValueDataProvider() {
		return array(
			array( 'Foo'  , 'Bar', 0, 1 ),
			array( '-Foo' , 'Bar', 1, 0 ),
			array( '_Foo' , 'Bar', 1, 0 ),
		);
	}

	/**
	 * @dataProvider getPropertyValueDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddDataValue( $propertyName, $value, $errorCount, $propertyCount ) {

		$instance = $this->newInstance();

		$instance->addDataValue(
			DataValueFactory::getInstance()->newPropertyValue(
				$propertyName,
				$value
			)
		);

		// Check the returned instance
		if ( $errorCount === 0 ){
			$expected['propertyCount']  = $propertyCount;
			$expected['propertyLabels'] = $propertyName;
			$expected['propertyValues'] = $value;
			$this->assertInstanceOf( '\SMW\SemanticData', $instance->getData() );

			$semanticDataValidator = new SemanticDataValidator;

			$semanticDataValidator->assertThatPropertiesAreSet(
				$expected,
				$instance->getData()
			);

		} else {
			$this->assertCount( $errorCount, $instance->getErrors() );
		}
	}

	/**
	 * @since 1.9
	 */
	public function testUpdateStore() {

		$notifier     = 'runStoreUpdater';
		$title        = $this->newTitle();
		$parserOutput = $this->newParserOutput();

		$instance = $this->newInstance( $title, $parserOutput );
		$observer = new MockUpdateObserver();

		$instance->registerDispatcher( new ObservableSubjectDispatcher( $observer ) );

		$this->assertTrue( $instance->updateStore() );

		// Verify that the Observer was notified
		$this->assertEquals( $notifier, $observer->getNotifier() );

	}

}
