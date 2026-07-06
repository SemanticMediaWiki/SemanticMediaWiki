<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use Exception;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use PHPUnit\Framework\TestCase;
use SMW\EventDispatcher\EventDispatcher;
use SMW\MediaWiki\EditInfo;
use SMW\MediaWiki\Hooks\RevisionFromEditComplete;
use SMW\MediaWiki\MwCollaboratorFactory;
use SMW\MediaWiki\PageInfoProvider;
use SMW\Property\AnnotatorFactory;
use SMW\Property\Annotators\NullPropertyAnnotator;
use SMW\Property\Annotators\PredefinedPropertyAnnotator;
use SMW\Property\Annotators\SchemaPropertyAnnotator;
use SMW\Schema\SchemaFactory;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use WikiPage;

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

	private $testEnvironment;
	private $eventDispatcher;
	private $propertyAnnotatorFactory;
	private $schemaFactory;
	private $store;
	private $mwCollaboratorFactory;
	private $userFactory;
	private $editInfo;
	private $pageInfoProvider;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->eventDispatcher = $this->createMock( EventDispatcher::class );
		$this->schemaFactory = $this->createMock( SchemaFactory::class );

		$nullAnnotator = $this->createMock( NullPropertyAnnotator::class );
		$predefinedAnnotator = $this->createMock( PredefinedPropertyAnnotator::class );
		$schemaAnnotator = $this->createMock( SchemaPropertyAnnotator::class );

		$this->propertyAnnotatorFactory = $this->createMock( AnnotatorFactory::class );
		$this->propertyAnnotatorFactory->method( 'newNullPropertyAnnotator' )->willReturn( $nullAnnotator );
		$this->propertyAnnotatorFactory->method( 'newPredefinedPropertyAnnotator' )->willReturn( $predefinedAnnotator );
		$this->propertyAnnotatorFactory->method( 'newSchemaPropertyAnnotator' )->willReturn( $schemaAnnotator );

		$this->editInfo = $this->createMock( EditInfo::class );
		$this->pageInfoProvider = $this->createMock( PageInfoProvider::class );

		$this->mwCollaboratorFactory = $this->createMock( MwCollaboratorFactory::class );
		$this->mwCollaboratorFactory->method( 'newEditInfo' )->willReturn( $this->editInfo );
		$this->mwCollaboratorFactory->method( 'newPageInfoProvider' )->willReturn( $this->pageInfoProvider );

		$this->userFactory = $this->createMock( UserFactory::class );
		$this->userFactory->method( 'newFromUserIdentity' )->willReturn( $this->createMock( User::class ) );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance( ?Store $store = null ): RevisionFromEditComplete {
		return new RevisionFromEditComplete(
			$this->propertyAnnotatorFactory,
			$this->schemaFactory,
			$store ?? $this->store,
			$this->eventDispatcher,
			$this->mwCollaboratorFactory,
			$this->userFactory
		);
	}

	private function newWikiPageWithTitle( Title $title ): WikiPage {
		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTitle' )->willReturn( $title );
		return $wikiPage;
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RevisionFromEditComplete::class,
			$this->newInstance()
		);
	}

	public function testProcess_NoParserOutput() {
		$title = $this->createMock( Title::class );

		$this->editInfo->expects( $this->once() )
			->method( 'getOutput' )
			->willReturn( null );

		$this->schemaFactory->expects( $this->never() )->method( 'newSchema' );
		$this->eventDispatcher->expects( $this->never() )->method( 'dispatch' );

		$tags = [];
		$this->newInstance()->onRevisionFromEditComplete(
			$this->newWikiPageWithTitle( $title ),
			null,
			0,
			$this->createMock( UserIdentity::class ),
			$tags
		);
	}

	public function testProcess_OnSchemaNamespace() {
		$parserOutput = $this->createMock( ParserOutput::class );

		$title = $this->createMock( Title::class );
		$title->method( 'getDBKey' )->willReturn( 'Foo' );
		$title->method( 'getNamespace' )->willReturn( SMW_NS_SCHEMA );

		$this->editInfo->method( 'getOutput' )->willReturn( $parserOutput );

		$this->schemaFactory->expects( $this->once() )->method( 'newSchema' );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ]
			);

		$tags = [];
		$this->newInstance()->onRevisionFromEditComplete(
			$this->newWikiPageWithTitle( $title ),
			null,
			0,
			$this->createMock( UserIdentity::class ),
			$tags
		);
	}

	public function testProcess_OnSchemaNamespace_InvalidSchema() {
		$parserOutput = $this->createMock( ParserOutput::class );

		$title = $this->createMock( Title::class );
		$title->method( 'getDBKey' )->willReturn( 'Foo' );
		$title->method( 'getNamespace' )->willReturn( SMW_NS_SCHEMA );

		$this->editInfo->method( 'getOutput' )->willReturn( $parserOutput );

		$this->schemaFactory->expects( $this->once() )
			->method( 'newSchema' )
			->willThrowException( new Exception() );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ]
			);

		$tags = [];
		$this->newInstance()->onRevisionFromEditComplete(
			$this->newWikiPageWithTitle( $title ),
			null,
			0,
			$this->createMock( UserIdentity::class ),
			$tags
		);
	}

	public function testProcess_OnConceptNamespace() {
		$store = $this->createMock( SQLStore::class );
		$store->expects( $this->once() )->method( 'deleteConceptCache' );

		$parserOutput = $this->createMock( ParserOutput::class );

		$title = $this->createMock( Title::class );
		$title->method( 'getDBKey' )->willReturn( 'Foo' );
		$title->method( 'getNamespace' )->willReturn( SMW_NS_CONCEPT );

		$this->editInfo->method( 'getOutput' )->willReturn( $parserOutput );

		$this->schemaFactory->expects( $this->never() )->method( 'newSchema' );

		$this->eventDispatcher->expects( $this->atLeastOnce() )
			->method( 'dispatch' )
			->withConsecutive(
				[ $this->equalTo( 'InvalidateResultCache' ) ],
				[ $this->equalTo( 'InvalidateEntityCache' ) ]
			);

		$tags = [];
		$this->newInstance( $store )->onRevisionFromEditComplete(
			$this->newWikiPageWithTitle( $title ),
			null,
			0,
			$this->createMock( UserIdentity::class ),
			$tags
		);
	}

}
