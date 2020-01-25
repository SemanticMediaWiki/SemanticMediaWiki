<?php

namespace SMW\Tests\Schema;

use SMW\Schema\SchemaFilterFactory;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Schema\SchemaFilterFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SchemaFilterFactoryTest extends \PHPUnit_Framework_TestCase {

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

}
