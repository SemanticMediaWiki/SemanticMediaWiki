<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use Article;
use MediaWiki\Context\RequestContext;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOptions;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DependencyValidator;
use SMW\DependencyValidatorFactory;
use SMW\MediaWiki\Hooks\ArticleViewHeader;
use SMW\NamespaceExaminer;
use SMW\Settings;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\Store;
use WikiPage as MwWikiPage;

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
	private $dependencyValidator;
	private $dependencyValidatorFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );

		$entityIdManager = $this->createMock( EntityIdManager::class );

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds' ] )
			->getMockForAbstractClass();

		$this->store->method( 'getObjectIds' )->willReturn( $entityIdManager );

		$this->settings = $this->createMock( Settings::class );

		$this->dependencyValidator = $this->createMock( DependencyValidator::class );

		$this->dependencyValidatorFactory = $this->createMock( DependencyValidatorFactory::class );
		$this->dependencyValidatorFactory->method( 'newFor' )->willReturn( $this->dependencyValidator );
	}

	private function newInstance(): ArticleViewHeader {
		return new ArticleViewHeader(
			$this->store,
			$this->namespaceExaminer,
			$this->settings,
			$this->dependencyValidatorFactory
		);
	}

	private function newArticleFor( $title ): Article {
		$wikiPage = $this->createMock( MwWikiPage::class );
		$wikiPage->method( 'makeParserOptions' )->willReturn( $this->createMock( ParserOptions::class ) );

		$article = $this->createMock( Article::class );
		$article->method( 'getTitle' )->willReturn( $title );
		$article->method( 'getPage' )->willReturn( $wikiPage );
		return $article;
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ArticleViewHeader::class,
			$this->newInstance()
		);
	}

	public function testProcessOnCategory() {
		$subject = WikiPage::newFromText( __METHOD__, NS_CATEGORY );
		$property = new Property( Property::TYPE_CHANGE_PROP );

		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( true );

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

		$this->store->method( 'getSemanticData' )->willReturn( $semanticData );

		$output = $this->createMock( OutputPage::class );
		$output->expects( $this->once() )->method( 'addHTML' );

		$context = $this->createMock( RequestContext::class );
		$context->method( 'getOutput' )->willReturn( $output );

		$wikiPage = $this->createMock( MwWikiPage::class );
		$wikiPage->method( 'makeParserOptions' )->willReturn( $this->createMock( ParserOptions::class ) );

		$page = $this->createMock( Article::class );
		$page->method( 'getTitle' )->willReturn( $subject->getTitle() );
		$page->method( 'getContext' )->willReturn( $context );
		$page->method( 'getPage' )->willReturn( $wikiPage );

		$outputDone = '';
		$useParserCache = '';

		$this->newInstance()->onArticleViewHeader( $page, $outputDone, $useParserCache );

		$this->assertFalse( $useParserCache );
	}

	public function testProcessOnNoCategory() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( false );

		$page = $this->createMock( Article::class );
		$page->method( 'getTitle' )->willReturn( $subject->getTitle() );
		$page->expects( $this->never() )->method( 'getContext' );

		$outputDone = '';
		$useParserCache = '';

		$this->assertTrue(
			$this->newInstance()->onArticleViewHeader( $page, $outputDone, $useParserCache )
		);
	}

	public function testHasArchaicDependency() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( true );

		$this->settings->method( 'get' )
			->willReturnMap( [
				[ 'smwgChangePropagationWatchlist', [ '_SUBC' ] ],
			] );

		$this->dependencyValidator->expects( $this->once() )
			->method( 'hasArchaicDependencies' )
			->willReturn( true );

		$this->dependencyValidator->expects( $this->once() )
			->method( 'markTitle' );

		$outputDone = '';
		$useParserCache = '';

		$this->assertTrue(
			$this->newInstance()->onArticleViewHeader(
				$this->newArticleFor( $subject->getTitle() ),
				$outputDone,
				$useParserCache
			)
		);
	}

}
