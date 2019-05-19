<?php

namespace SMW\Tests;

use SMW\DataItemFactory;
use SMW\DIWikiPage;
use SMW\ProcessingErrorMsgHandler;

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

	public function testGrepPropertyFromRestrictionErrorMsg() {

		$this->assertNull(
			ProcessingErrorMsgHandler::grepPropertyFromRestrictionErrorMsg( 'Foo' )
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

		$expected = [
			'propertyCount' => 2,
			'propertyKeys'  => [ '_ERRP', '_ERRT' ],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$container->getSemanticData()
		);
	}

	public function testGetErrorContainerFromMsg_TypedError() {

		$processingError = $this->getMockBuilder( '\SMW\ProcessingError' )
			->disableOriginalConstructor()
			->getMock();

		$processingError->expects( $this->atLeastOnce() )
			->method( 'encode' )
			->will( $this->returnValue( 'foo' ) );

		$processingError->expects( $this->atLeastOnce() )
			->method( 'getType' )
			->will( $this->returnValue( 'foobar' ) );

		$instance = new ProcessingErrorMsgHandler(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$property = $this->dataItemFactory->newDIProperty( 'Bar' );
		$container = $instance->newErrorContainerFromMsg( $processingError, $property );

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$container
		);

		$expected = [
			'propertyCount' => 3,
			'propertyKeys'  => [ '_ERRP', '_ERRT', '_ERR_TYPE' ],
		];

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

		$expected = [
			'propertyCount' => 1,
			'propertyKeys'  => [ '_ERRT' ],
		];

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
			->setMethods( [ 'getErrors', 'getProperty' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( [ 'Foo' ] ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Bar' ) ) );

		$container = $instance->newErrorContainerFromDataValue( $dataValue );

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$container
		);

		$expected = [
			'propertyCount' => 2,
			'propertyKeys'  => [ '_ERRP', '_ERRT' ],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$container->getSemanticData()
		);
	}

	public function testGetErrorContainerFromDataValue_CategoryProperty() {

		$instance = new ProcessingErrorMsgHandler(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getErrors', 'getProperty' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( [ 'Foo' ] ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( '_INST' ) ) );

		$container = $instance->newErrorContainerFromDataValue( $dataValue );

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$container
		);

		$expected = [
			'propertyCount' => 1,
			'propertyKeys'  => [ '_ERRT' ],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$container->getSemanticData()
		);
	}

	public function testGetErrorContainerFromDataValue_TypedError() {

		$instance = new ProcessingErrorMsgHandler(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'getErrors', 'getErrorsByType', 'getProperty' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getErrors' )
			->will( $this->returnValue( [ '_123' => 'Foo' ] ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getErrorsByType' )
			->will( $this->returnValue( [ '_type_1' => [ '_123' ] ] ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Bar' ) ) );

		$container = $instance->newErrorContainerFromDataValue( $dataValue );

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$container
		);

		$expected = [
			'propertyCount' => 3,
			'propertyKeys'  => [ '_ERRP', '_ERRT', '_ERR_TYPE' ],
		];

		$this->semanticDataValidator->assertThatPropertiesAreSet(
			$expected,
			$container->getSemanticData()
		);
	}

	public function messagesProvider() {

		$provider[] = [
			[],
			[]
		];

		$provider[] = [
			[ 'Foo' ],
			[ 'Foo' ]
		];

		$provider[] = [
			[ 'Foo', [ 'Bar' ] ],
			[ 'Foo', 'Bar' ]
		];

		$provider[] = [
			[ 'smw-title', [ 'smw-title' ] ],
			[ 'Semantic MediaWiki' ]
		];

		$provider[] = [
			[ 'Foo', [ 'Bar', [ 'Bar' ] ] ],
			[ 'Foo', 'Bar', ]
		];

		$provider[] = [
			[ 'Foo', [ 'Bar', [ 'Bar' ], new \stdClass ] ],
			[ 'Foo', 'Bar', new \stdClass ]
		];

		$provider[] = [
			[ 'Foo', [ 'Bar', [ 'Bar', new \stdClass ], new \stdClass ], 'Foobar' ],
			[ 'Foo', 'Bar', new \stdClass, new \stdClass, 'Foobar' ]
		];

		$provider[] = [
			[ 'Foo', [ '[2,"smw-title"]' ] ],
			[ 'Foo' , 'Semantic MediaWiki' ]
		];

		return $provider;
	}

}
