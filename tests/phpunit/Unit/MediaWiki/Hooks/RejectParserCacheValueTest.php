<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DataItems\WikiPage as DIWikiPage;
use SMW\DependencyValidatorFactory;
use SMW\EventDispatcher\EventDispatcher;
use SMW\MediaWiki\Hooks\RejectParserCacheValue;
use SMW\NamespaceExaminer;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Tests\TestEnvironment;
use Wikimedia\ObjectCache\HashBagOStuff;
use Wikimedia\Timestamp\ConvertibleTimestamp;
use WikiPage;

/**
 * @covers \SMW\MediaWiki\Hooks\RejectParserCacheValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class RejectParserCacheValueTest extends TestCase {

	private $testEnvironment;
	private $namespaceExaminer;
	private $logger;
	private $dependencyLinksValidator;
	private $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$this->logger = $this->createMock( LoggerInterface::class );
		$this->dependencyLinksValidator = $this->createMock( DependencyLinksValidator::class );
		$this->cache = new HashBagOStuff();
	}

	protected function tearDown(): void {
		ConvertibleTimestamp::setFakeTime( false );
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	private function newInstance(): RejectParserCacheValue {
		return new RejectParserCacheValue(
			$this->namespaceExaminer,
			$this->logger,
			$this->newDependencyValidatorFactory()
		);
	}

	private function newDependencyValidatorFactory(): DependencyValidatorFactory {
		$queryDependencyLinksStoreFactory = $this->createMock( QueryDependencyLinksStoreFactory::class );
		$queryDependencyLinksStoreFactory->method( 'newDependencyLinksValidator' )
			->willReturn( $this->dependencyLinksValidator );

		return new DependencyValidatorFactory(
			$this->namespaceExaminer,
			$queryDependencyLinksStoreFactory,
			$this->cache,
			$this->createMock( EventDispatcher::class )
		);
	}

	private function newPage( Title $title ): WikiPage {
		$page = $this->createMock( WikiPage::class );
		$page->method( 'getTitle' )->willReturn( $title );
		return $page;
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			RejectParserCacheValue::class,
			$this->newInstance()
		);
	}

	public function testKeepsOutputOfPageOutsideSemanticNamespaces() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );
		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( false );

		$this->assertTrue( $this->keepsOutputRenderedAt( $title, '20260601115959' ) );
	}

	public function testKeepsOutputRenderedAfterOutdatedDependenciesWereFound() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );

		$this->assertTrue( $this->keepsOutputRenderedAt( $title, '20260601120001' ) );
	}

	public function testRejectsOutputRenderedBeforeOutdatedDependenciesWereFound() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );

		$this->assertFalse( $this->keepsOutputRenderedAt( $title, '20260601115959' ) );
	}

	private function findOutdatedDependenciesAt( Title $title, string $time ): void {
		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( true );
		$this->dependencyLinksValidator->method( 'canCheckDependencies' )->willReturn( true );
		$this->dependencyLinksValidator->method( 'hasArchaicDependencies' )->willReturn( true );

		ConvertibleTimestamp::setFakeTime( $time );
		$this->newDependencyValidatorFactory()
			->newDependencyValidator()
			->hasArchaicDependencies( DIWikiPage::newFromTitle( $title ) );
	}

	private function keepsOutputRenderedAt( Title $title, string $cacheTime ): bool {
		$parserOutput = new ParserOutput();
		$parserOutput->setCacheTime( $cacheTime );

		return $this->newInstance()->onRejectParserCacheValue(
			$parserOutput,
			$this->newPage( $title ),
			$this->createMock( ParserOptions::class )
		);
	}

}
