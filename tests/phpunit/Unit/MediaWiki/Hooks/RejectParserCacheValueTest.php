<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DataItems\WikiPage as DIWikiPage;
use SMW\DependencyValidatorFactory;
use SMW\EntityCache;
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
	private $entityCache;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$this->logger = $this->createMock( LoggerInterface::class );
		$this->dependencyLinksValidator = $this->createMock( DependencyLinksValidator::class );
		$this->entityCache = new EntityCache( new HashBagOStuff() );
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
			$this->entityCache,
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
		$this->namespaceExaminer->method( 'isSemanticEnabled' )->willReturn( false );

		$this->assertTrue(
			$this->newInstance()->onRejectParserCacheValue(
				$this->newParserOutputRenderedAt( '20260601115959' ),
				$this->newPage( $title ),
				$this->createMock( ParserOptions::class )
			)
		);
	}

	public function testKeepsOutputRenderedAfterOutdatedDependenciesWereFound() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );

		$this->assertTrue(
			$this->newInstance()->onRejectParserCacheValue(
				$this->newParserOutputRenderedAt( '20260601120001' ),
				$this->newPage( $title ),
				$this->createMock( ParserOptions::class )
			)
		);
	}

	public function testRejectsOutputRenderedBeforeOutdatedDependenciesWereFound() {
		$title = Title::makeTitle( NS_MAIN, 'Foo' );
		$this->findOutdatedDependenciesAt( $title, '20260601120000' );

		$this->assertFalse(
			$this->newInstance()->onRejectParserCacheValue(
				$this->newParserOutputRenderedAt( '20260601115959' ),
				$this->newPage( $title ),
				$this->createMock( ParserOptions::class )
			)
		);
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

	private function newParserOutputRenderedAt( string $cacheTime ): ParserOutput {
		$parserOutput = new ParserOutput();
		$parserOutput->setCacheTime( $cacheTime );

		return $parserOutput;
	}

}
