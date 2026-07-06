<?php

namespace SMW\Tests\Unit\Schema;

use PHPUnit\Framework\TestCase;
use SMW\Schema\Filters\CategoryFilter;
use SMW\Schema\Filters\CompositeFilter;
use SMW\Schema\Filters\NamespaceFilter;
use SMW\Schema\Filters\PropertyFilter;
use SMW\Schema\SchemaFilterFactory;

/**
 * @covers \SMW\Schema\SchemaFilterFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class SchemaFilterFactoryTest extends TestCase {

	public function testCanConstructCompositeFilter() {
		$instance = new SchemaFilterFactory();

		$this->assertInstanceof(
			CompositeFilter::class,
			$instance->newCompositeFilter( [] )
		);
	}

	public function testCanConstructNamespaceFilter() {
		$instance = new SchemaFilterFactory();

		$this->assertInstanceof(
			NamespaceFilter::class,
			$instance->newNamespaceFilter( NS_MAIN )
		);
	}

	public function testCanConstructCategoryFilter() {
		$instance = new SchemaFilterFactory();

		$this->assertInstanceof(
			CategoryFilter::class,
			$instance->newCategoryFilter( 'Foo' )
		);
	}

	public function testCanConstructPropertyFilter() {
		$instance = new SchemaFilterFactory();

		$this->assertInstanceof(
			PropertyFilter::class,
			$instance->newPropertyFilter( 'Foo' )
		);
	}

}
