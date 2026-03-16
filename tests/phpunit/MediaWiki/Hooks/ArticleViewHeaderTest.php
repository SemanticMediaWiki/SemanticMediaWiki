<?php

namespace SMW\Tests\MediaWiki\Hooks;

use MediaWiki\Context\RequestContext;
use MediaWiki\Output\OutputPage;
use PHPUnit\Framework\TestCase;
use SMW\DependencyValidator;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Hooks\ArticleViewHeader;
use SMW\NamespaceExaminer;
use SMW\SemanticData;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Hooks\ArticleViewHeader
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleViewHeaderTest extends TestCase {

	private $testEnvironment;
	private $store;
	private $namespaceExaminer;
	private $dependencyValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->namespaceExaminer = $this->getMockBuilder( NamespaceExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$this->dependencyValidator = $this->getMockBuilder( DependencyValidator::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ArticleViewHeader::class,
			new ArticleViewHeader( $this->store, $this->namespaceExaminer, $this->dependencyValidator )
		);
	}

	public function testProcessOnCategory() {
		$subject = DIWikiPage::newFromText( __METHOD__, NS_CATEGORY );
		$property = new DIProperty( DIProperty::TYPE_CHANGE_PROP );

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'hasProperty' )
			->with( $property )
			->willReturn( true );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$output = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$output->expects( $this->once() )
			->method( 'addHtml' );

		$context = $this->getMockBuilder( RequestContext::class )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $output );

		$page = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $subject->getTitle() );

		$page->expects( $this->any() )
			->method( 'getContext' )
			->willReturn( $context );

		$instance = new ArticleViewHeader(
			$this->store,
			$this->namespaceExaminer,
			$this->dependencyValidator
		);

		$instance->setOptions(
			[
				'smwgChangePropagationWatchlist' => [ '_SUBC' ]
			]
		);

		$outputDone = '';
		$useParserCache = '';

		$instance->process( $page, $outputDone, $useParserCache );

		$this->assertFalse(
			$useParserCache
		);
	}

	public function testProcessOnNoCategory() {
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$output = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$page = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $subject->getTitle() );

		$page->expects( $this->never() )
			->method( 'getContext' );

		$instance = new ArticleViewHeader(
			$this->store,
			$this->namespaceExaminer,
			$this->dependencyValidator
		);

		$instance->setOptions(
			[
				'smwgChangePropagationWatchlist' => [ '_SUBC' ]
			]
		);

		$outputDone = '';
		$useParserCache = '';

		$instance->process( $page, $outputDone, $useParserCache );
	}

	public function testHasArchaicDependency() {
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->dependencyValidator->expects( $this->any() )
			->method( 'hasArchaicDependencies' )
			->willReturn( true );

		$title = $subject->getTitle();

		$output = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$page = $this->getMockBuilder( '\Article' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getTitle' )
			->willReturn( $title );

		$page->expects( $this->never() )
			->method( 'getContext' );

		$instance = new ArticleViewHeader(
			$this->store,
			$this->namespaceExaminer,
			$this->dependencyValidator
		);

		$instance->setOptions(
			[
				'smwgChangePropagationWatchlist' => [ '_SUBC' ]
			]
		);

		$outputDone = '';
		$useParserCache = '';

		$instance->process( $page, $outputDone, $useParserCache );

		$this->assertFalse(
			$useParserCache
		);
	}

}
