<?php

namespace SMW\Tests\Unit\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\QueryDependency\DependencyLinksValidator;
use SMW\SQLStore\QueryDependency\QueryDependencyLinksStore;
use SMW\SQLStore\QueryDependency\QueryLinksTableDisposer;
use SMW\SQLStore\QueryDependency\QueryReferenceBacklinks;
use SMW\SQLStore\QueryDependency\QueryResultDependencyListResolver;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\Store;

/**
 * @covers \SMW\SQLStore\QueryDependencyLinksStoreFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class QueryDependencyLinksStoreFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryDependencyLinksStoreFactory::class,
			new QueryDependencyLinksStoreFactory()
		);
	}

	public function testCanConstructQueryResultDependencyListResolver() {
		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			QueryResultDependencyListResolver::class,
			$instance->newQueryResultDependencyListResolver()
		);
	}

	public function testCanConstructQueryDependencyLinksStore() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			QueryDependencyLinksStore::class,
			$instance->newQueryDependencyLinksStore( $store )
		);
	}

	public function testCanConstructQueryReferenceBacklinks() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			QueryReferenceBacklinks::class,
			$instance->newQueryReferenceBacklinks( $store )
		);
	}

	public function testCanConstructDependencyLinksValidator() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			DependencyLinksValidator::class,
			$instance->newDependencyLinksValidator()
		);
	}

	public function testCanConstructQueryLinksTableDisposer() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new QueryDependencyLinksStoreFactory();

		$this->assertInstanceOf(
			QueryLinksTableDisposer::class,
			$instance->newQueryLinksTableDisposer( $store )
		);
	}

}
