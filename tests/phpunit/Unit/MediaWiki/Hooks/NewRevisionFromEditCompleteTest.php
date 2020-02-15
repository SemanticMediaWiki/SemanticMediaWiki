<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\NewRevisionFromEditComplete;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\NewRevisionFromEditComplete
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NewRevisionFromEditCompleteTest extends \PHPUnit_Framework_TestCase {

	private $semanticDataValidator;
	private $testEnvironment;
	private $eventDispatcher;
	private $editInfo;
	private $propertyAnnotatorFactory;
	private $schemaFactory;

	protected function setUp() : void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->eventDispatcher = $this->getMockBuilder( '\Onoi\EventDispatcher\EventDispatcher' )
			->disableOriginalConstructor()
			->getMock();

		$this->editInfo = $this->getMockBuilder( '\SMW\MediaWiki\EditInfo' )
			->disableOriginalConstructor()
			->getMock();

		$annotator = $this->getMockBuilder( '\SMW\Property\Annotator' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyAnnotatorFactory = $this->getMockBuilder( '\SMW\Property\AnnotatorFactory' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyAnnotatorFactory->expects( $this->any() )
			->method( 'newNullPropertyAnnotator' )
			->will( $this->returnValue( $annotator ) );

		$this->propertyAnnotatorFactory->expects( $this->any() )
			->method( 'newPredefinedPropertyAnnotator' )
			->will( $this->returnValue( $annotator ) );

		$this->propertyAnnotatorFactory->expects( $this->any() )
			->method( 'newSchemaPropertyAnnotator' )
			->will( $this->returnValue( $annotator ) );

		$this->schemaFactory = $this->getMockBuilder( '\SMW\Schema\SchemaFactory' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() : void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$pageInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\PageInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			NewRevisionFromEditComplete::class,
			new NewRevisionFromEditComplete( $this->editInfo, $pageInfoProvider, $this->propertyAnnotatorFactory, $this->schemaFactory )
		);
	}

	public function testProcess_NoParserOutput() {

		$pageInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\PageInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->editInfo->expects( $this->once() )
			->method( 'getOutput' )
			->will( $this->returnValue( null ) );

		$this->schemaFactory->expects( $this->never() )
			->method( 'newSchema' );

		$this->eventDispatcher->expects( $this->never() )
			->method( 'dispatch' );

		$instance = new NewRevisionFromEditComplete(
			$this->editInfo,
			$pageInfoProvider,
			$this->propertyAnnotatorFactory,
			$this->schemaFactory
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->process( $title );
	}

	public function testProcess_OnSchemaNamespace() {

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\PageInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$this->editInfo->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$this->schemaFactory->expects( $this->once() )
			->method( 'newSchema' );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$instance = new NewRevisionFromEditComplete(
			$this->editInfo,
			$pageInfoProvider,
			$this->propertyAnnotatorFactory,
			$this->schemaFactory
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->process( $title );
	}

	public function testProcess_OnSchemaNamespace_InvalidSchema() {

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\PageInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_SCHEMA ) );

		$this->editInfo->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$this->schemaFactory->expects( $this->once() )
			->method( 'newSchema' )
			->will( $this->throwException( new \Exception() ) );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$instance = new NewRevisionFromEditComplete(
			$this->editInfo,
			$pageInfoProvider,
			$this->propertyAnnotatorFactory,
			$this->schemaFactory
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->process( $title );
	}

	public function testProcess_OnConceptNamespace() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'deleteConceptCache' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$parserOutput = $this->getMockBuilder( '\ParserOutput' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfoProvider = $this->getMockBuilder( '\SMW\MediaWiki\PageInfoProvider' )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->will( $this->returnValue( 'Foo' ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( SMW_NS_CONCEPT ) );

		$this->editInfo->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $parserOutput ) );

		$this->schemaFactory->expects( $this->never() )
			->method( 'newSchema' );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$instance = new NewRevisionFromEditComplete(
			$this->editInfo,
			$pageInfoProvider,
			$this->propertyAnnotatorFactory,
			$this->schemaFactory
		);

		$instance->setEventDispatcher(
			$this->eventDispatcher
		);

		$instance->process( $title );
	}

}
