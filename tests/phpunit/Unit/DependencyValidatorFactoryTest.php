<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\DependencyValidatorFactory;
use SMW\EntityCache;
use SMW\EventDispatcher\EventDispatcher;
use SMW\NamespaceExaminer;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;

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

	protected function setUp(): void {
		parent::setUp();

		$this->namespaceExaminer = $this->createMock( NamespaceExaminer::class );
		$this->queryDependencyLinksStoreFactory = $this->createMock( QueryDependencyLinksStoreFactory::class );
		$this->entityCache = $this->createMock( EntityCache::class );
		$this->eventDispatcher = $this->createMock( EventDispatcher::class );
	}

	private function newInstance(): DependencyValidatorFactory {
		return new DependencyValidatorFactory(
			$this->namespaceExaminer,
			$this->queryDependencyLinksStoreFactory,
			$this->entityCache,
			$this->eventDispatcher
		);
	}

	public function testCanConstruct(): void {
		$this->assertInstanceOf(
			DependencyValidatorFactory::class,
			$this->newInstance()
		);
	}

	public function testBuildsAFreshValidatorPerCall(): void {
		$this->queryDependencyLinksStoreFactory->expects( $this->exactly( 2 ) )
			->method( 'newDependencyLinksValidator' )
			->willReturn( $this->createMock( DependencyLinksValidator::class ) );

		$instance = $this->newInstance();
		$instance->newDependencyValidator();
		$instance->newDependencyValidator();
	}

}
