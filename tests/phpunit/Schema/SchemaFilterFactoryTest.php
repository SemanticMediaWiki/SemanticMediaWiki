<?php

namespace SMW\Tests\Schema;

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
class SchemaFilterFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstructCompositeFilter() {
		$instance = new SchemaFilterFactory();

		$this->assertInstanceof(
			'\SMW\Schema\Filters\CompositeFilter',
			$instance->newCompositeFilter( [] )
		);
	}

	public function testCanConstructNamespaceFilter() {
		$instance = new SchemaFilterFactory();

		$this->assertInstanceof(
			'\SMW\Schema\Filters\NamespaceFilter',
			$instance->newNamespaceFilter( NS_MAIN )
		);
	}

	public function testCanConstructCategoryFilter() {
		$instance = new SchemaFilterFactory();

		$this->assertInstanceof(
			'\SMW\Schema\Filters\CategoryFilter',
			$instance->newCategoryFilter( 'Foo' )
		);
	}

	public function testCanConstructPropertyFilter() {
		$instance = new SchemaFilterFactory();

		$this->assertInstanceof(
			'\SMW\Schema\Filters\PropertyFilter',
			$instance->newPropertyFilter( 'Foo' )
		);
	}

}
