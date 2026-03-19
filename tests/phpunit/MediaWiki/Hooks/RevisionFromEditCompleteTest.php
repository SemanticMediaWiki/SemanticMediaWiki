<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use Onoi\EventDispatcher\EventDispatcher;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\EditInfo;
use SMW\MediaWiki\Hooks\RevisionFromEditComplete;
use SMW\MediaWiki\PageInfoProvider;
use SMW\Property\AnnotatorFactory;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\PredefinedPropertyAnnotator;
use SMW\Property\Annotators\SchemaPropertyAnnotator;
use SMW\Schema\SchemaFactory;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\RevisionFromEditComplete
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class RevisionFromEditCompleteTest extends TestCase {

	private $semanticDataValidator;
	private $testEnvironment;
	private $eventDispatcher;
	private $editInfo;
	private $propertyAnnotatorFactory;
	private $schemaFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->semanticDataValidator = $this->testEnvironment->getUtilityFactory()->newValidatorFactory()->newSemanticDataValidator();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );

		$this->eventDispatcher = $this->getMockBuilder( EventDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->editInfo = $this->getMockBuilder( EditInfo::class )
			->disableOriginalConstructor()
			->getMock();

		$nullAnnotator = $this->getMockBuilder( NullPropertyAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$predefinedAnnotator = $this->getMockBuilder( PredefinedPropertyAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$schemaAnnotator = $this->getMockBuilder( SchemaPropertyAnnotator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyAnnotatorFactory = $this->getMockBuilder( AnnotatorFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyAnnotatorFactory->expects( $this->any() )
			->method( 'newNullPropertyAnnotator' )
			->willReturn( $nullAnnotator );

		$this->propertyAnnotatorFactory->expects( $this->any() )
			->method( 'newPredefinedPropertyAnnotator' )
			->willReturn( $predefinedAnnotator );

		$this->propertyAnnotatorFactory->expects( $this->any() )
			->method( 'newSchemaPropertyAnnotator' )
			->willReturn( $schemaAnnotator );

		$this->schemaFactory = $this->getMockBuilder( SchemaFactory::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$pageInfoProvider = $this->getMockBuilder( PageInfoProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			RevisionFromEditComplete::class,
			new RevisionFromEditComplete( $this->editInfo, $pageInfoProvider, $this->propertyAnnotatorFactory, $this->schemaFactory )
		);
	}

	public function testProcess_NoParserOutput() {
		$pageInfoProvider = $this->getMockBuilder( PageInfoProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->editInfo->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( null );

		$this->schemaFactory->expects( $this->never() )
			->method( 'newSchema' );

		$this->eventDispatcher->expects( $this->never() )
			->method( 'dispatch' );

		$instance = new RevisionFromEditComplete(
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
		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();

		$pageInfoProvider = $this->getMockBuilder( PageInfoProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( SMW_NS_SCHEMA );

		$this->editInfo->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$this->schemaFactory->expects( $this->once() )
			->method( 'newSchema' );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$instance = new RevisionFromEditComplete(
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
		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();

		$pageInfoProvider = $this->getMockBuilder( PageInfoProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( SMW_NS_SCHEMA );

		$this->editInfo->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$this->schemaFactory->expects( $this->once() )
			->method( 'newSchema' )
			->willThrowException( new \Exception() );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$instance = new RevisionFromEditComplete(
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
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'deleteConceptCache' );

		$this->testEnvironment->registerObject( 'Store', $store );

		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();

		$pageInfoProvider = $this->getMockBuilder( PageInfoProvider::class )
			->disableOriginalConstructor()
			->getMock();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'getDBKey' )
			->willReturn( 'Foo' );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( SMW_NS_CONCEPT );

		$this->editInfo->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $parserOutput );

		$this->schemaFactory->expects( $this->never() )
			->method( 'newSchema' );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ] );

		$instance = new RevisionFromEditComplete(
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
