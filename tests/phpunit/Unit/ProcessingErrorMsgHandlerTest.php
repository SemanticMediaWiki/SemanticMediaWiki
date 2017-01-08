<?php

namespace SMW\Tests;

use SMW\DataItemFactory;
use SMW\DIWikiPage;
use SMW\ProcessingErrorMsgHandler;
use SMW\Message;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\ProcessingErrorMsgHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ProcessingErrorMsgHandlerTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $testEnvironment;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();
	}

	public function testCanConstruct() {

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\ProcessingErrorMsgHandler',
			new ProcessingErrorMsgHandler( $subject )
		);
	}

	/**
	 * @dataProvider messagesProvider
	 */
	public function testNormalizeMessages( $messages, $expected ) {

		$this->assertEquals(
			$expected,
			ProcessingErrorMsgHandler::normalizeAndDecodeMessages( $messages, null, 'en' )
		);

		$this->assertInternalType(
			'string',
			ProcessingErrorMsgHandler::getMessagesAsString( $messages, null, 'en' )
		);
	}

	public function testPush() {

		$instance = new ProcessingErrorMsgHandler(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$container = $this->getMockBuilder( '\SMWDIContainer' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'addPropertyObjectValue' )
			->with( $this->equalTo( $this->dataItemFactory->newDIProperty( '_ERRC' ) ) );

		$instance->addToSemanticData(
			$semanticData,
			$container
		);
	}

	public function testGetErrorContainerFromMsg() {

		$instance = new ProcessingErrorMsgHandler(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$property = $this->dataItemFactory->newDIProperty( 'Bar' );
		$container = $instance->newErrorContainerFromMsg( 'foo', $property );

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$container
		);

		$expected = array(
			'propertyCount' => 2,
			'propertyKeys'  => array( '_ERRP', '_ERRT' ),
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$container->getSemanticData()
		);
	}

	public function testGetErrorContainerFromMsgWithoutProperty() {

		$instance = new ProcessingErrorMsgHandler(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$container = $instance->newErrorContainerFromMsg( 'foo' );

		$expected = array(
			'propertyCount' => 1,
			'propertyKeys'  => array( '_ERRT' ),
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$container->getSemanticData()
		);
	}

	public function testGetErrorContainerFromDataValue() {

		$instance = new ProcessingErrorMsgHandler(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( array( 'getErrors', 'getProperty' ) )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( array( 'Foo' ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Bar' ) ) );

		$container = $instance->newErrorContainerFromDataValue( $dataValue );

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$container
		);

		$expected = array(
			'propertyCount' => 2,
			'propertyKeys'  => array( '_ERRP', '_ERRT' ),
		);

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$container->getSemanticData()
		);
	}

	public function messagesProvider() {

		$provider[] = array(
			array(),
			array()
		);

		$provider[] = array(
			array( 'Foo' ),
			array( 'Foo' )
		);

		$provider[] = array(
			array( 'Foo', array( 'Bar' ) ),
			array( 'Foo', 'Bar' )
		);

		$provider[] = array(
			array( 'smw-title', array( 'smw-title' ) ),
			array( 'Semantic MediaWiki' )
		);

		$provider[] = array(
			array( 'Foo', array( 'Bar', array( 'Bar' ) ) ),
			array( 'Foo', 'Bar', )
		);

		$provider[] = array(
			array( 'Foo', array( 'Bar', array( 'Bar' ), new \stdClass ) ),
			array( 'Foo', 'Bar', new \stdClass )
		);

		$provider[] = array(
			array( 'Foo', array( 'Bar', array( 'Bar', new \stdClass ), new \stdClass ), 'Foobar' ),
			array( 'Foo', 'Bar', new \stdClass, new \stdClass, 'Foobar' )
		);

		$provider[] = array(
			array( 'Foo', array( '[2,"smw-title"]' ) ),
			array( 'Foo' , 'Semantic MediaWiki' )
		);

		return $provider;
	}

}
