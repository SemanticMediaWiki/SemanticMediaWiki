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
	private DependencyLinksValidator $dependencyLinksValidator;
	private EntityCache $entityCache;
	private EventDispatcher $eventDispatcher;
	private ParserCache $parserCache;

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$this->dependencyLinksValidator = $this->createMock( DependencyLinksValidator::class );
		$this->entityCache = $this->createMock( EntityCache::class );
		$this->eventDispatcher = $this->createMock( EventDispatcher::class );
		$this->parserCache = $this->createMock( ParserCache::class );
	}

	private function newInstance(): DependencyValidatorFactory {
		return new DependencyValidatorFactory(
			$this->namespaceExaminer,
			$this->dependencyLinksValidator,
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

		$validator = $this->newInstance()->newFor( $wikiPage, $parserOptions );

		$this->assertInstanceOf( DependencyValidator::class, $validator );
	}

}
