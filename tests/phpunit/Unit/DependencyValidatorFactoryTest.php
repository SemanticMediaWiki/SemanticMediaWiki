<?php

namespace SMW\Tests\Unit;

use MediaWiki\Parser\ParserCache;
use MediaWiki\Parser\ParserOptions;
use PHPUnit\Framework\TestCase;
use SMW\DependencyValidator;
use SMW\DependencyValidatorFactory;
use SMW\EntityCache;
use SMW\EventDispatcher\EventDispatcher;
use SMW\NamespaceExaminer;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use WikiPage;

/**
 * @covers \SMW\DependencyValidatorFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class DependencyValidatorFactoryTest extends TestCase {

	private NamespaceExaminer $namespaceExaminer;
	private QueryDependencyLinksStoreFactory $queryDependencyLinksStoreFactory;
	private EntityCache $entityCache;
	private EventDispatcher $eventDispatcher;
	private ParserCache $parserCache;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$this->queryDependencyLinksStoreFactory = $this->createMock( QueryDependencyLinksStoreFactory::class );
		$this->entityCache = $this->createMock( EntityCache::class );
		$this->eventDispatcher = $this->createMock( EventDispatcher::class );
		$this->parserCache = $this->createMock( ParserCache::class );
	}

	private function newInstance(): DependencyValidatorFactory {
		return new DependencyValidatorFactory(
			$this->namespaceExaminer,
			$this->queryDependencyLinksStoreFactory,
			$this->entityCache,
			$this->eventDispatcher,
			$this->parserCache
		);
	}

	public function testCanConstruct(): void {
		$this->assertInstanceOf(
			DependencyValidatorFactory::class,
			$this->newInstance()
		);
	}

	public function testNewForReturnsConfiguredValidator(): void {
		$wikiPage = $this->createMock( WikiPage::class );
		$wikiPage->method( 'getTouched' )->willReturn( '20260526120000' );

		$parserOptions = $this->createMock( ParserOptions::class );

		$this->parserCache->expects( $this->once() )
			->method( 'makeParserOutputKey' )
			->with( $wikiPage, $parserOptions )
			->willReturn( 'parsercache-key' );

		$this->queryDependencyLinksStoreFactory->expects( $this->once() )
			->method( 'newDependencyLinksValidator' )
			->willReturn( $this->createMock( DependencyLinksValidator::class ) );

		$validator = $this->newInstance()->newFor( $wikiPage, $parserOptions );

		$this->assertInstanceOf( DependencyValidator::class, $validator );
	}

	public function testNewForBuildsAFreshValidatorPerCall(): void {
		$wikiPage = $this->createMock( WikiPage::class );
		$parserOptions = $this->createMock( ParserOptions::class );

		// Each invocation must request a fresh validator from the factory so
		// that a cached handler instance cannot retain a stale Store-bound
		// validator across service-container resets.
		$this->queryDependencyLinksStoreFactory->expects( $this->exactly( 2 ) )
			->method( 'newDependencyLinksValidator' )
			->willReturn( $this->createMock( DependencyLinksValidator::class ) );

		$instance = $this->newInstance();
		$instance->newFor( $wikiPage, $parserOptions );
		$instance->newFor( $wikiPage, $parserOptions );
	}

}
