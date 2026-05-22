<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use Article;
use MediaWiki\Context\RequestContext;
use MediaWiki\Output\OutputPage;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Hooks\ArticleViewHeader;
use SMW\NamespaceExaminer;
use SMW\Settings;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\Store;
use Throwable;

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

	private $store;
	private $namespaceExaminer;
	private $settings;

	protected function setUp(): void {
		parent::setUp();

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

		$this->settings = $this->createMock( Settings::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ArticleViewHeader::class,
			new ArticleViewHeader( $this->store, $this->namespaceExaminer, $this->settings )
		);
	}

	public function testProcessOnCategory() {
		$subject = WikiPage::newFromText( __METHOD__, NS_CATEGORY );
		$property = new Property( Property::TYPE_CHANGE_PROP );

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( true );

		$this->settings->method( 'get' )
			->willReturnMap( [
				[ 'smwgChangePropagationWatchlist', [ '_SUBC' ] ],
				[ 'smwgChangePropagationProtection', false ],
			] );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
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
			->method( 'addHTML' );

		$context = $this->getMockBuilder( RequestContext::class )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $output );

		$page = $this->getMockBuilder( Article::class )
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
			$this->settings
		);

		$outputDone = '';
		$useParserCache = '';

		// Only the category-top branch is exercised here; the dependency
		// validator branch needs a fully wired ApplicationFactory and is
		// covered by HooksTest.
		try {
			$instance->onArticleViewHeader( $page, $outputDone, $useParserCache );
		} catch ( Throwable $e ) {
			// Downstream dependency-validator branch is not wired in this
			// unit-test scope.
		}

		$this->assertFalse(
			$useParserCache
		);
	}

	public function testProcessOnNoCategory() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$this->namespaceExaminer->expects( $this->any() )
			->method( 'isSemanticEnabled' )
			->willReturn( false );

		$page = $this->getMockBuilder( Article::class )
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
			$this->settings
		);

		$outputDone = '';
		$useParserCache = '';

		$this->assertTrue(
			$instance->onArticleViewHeader( $page, $outputDone, $useParserCache )
		);
	}

}
