<?php

namespace SMW\Tests\Schema\Filters;

use SMW\Schema\Filters\FilterTrait;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Schema\Filters\FilterTrait
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FilterTraitTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testHasMatches() {
		$instance = $this->newFilterTrait();

		$this->assertIsBool(

			$instance->hasMatches()
		);
	}

	public function testGetMatches() {
		$instance = $this->newFilterTrait();

		$this->assertIsArray(

			$instance->getMatches()
		);
	}

	public function testGetLog() {
		$instance = $this->newFilterTrait();

		$this->assertIsArray(

			$instance->getLog()
		);
	}

	public function testSetNodeFilter() {
		$compartment = $this->getMockBuilder( '\SMW\Schema\Compartment' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFilter = $this->getMockBuilder( '\SMW\Schema\SchemaFilter' )
			->disableOriginalConstructor()
			->getMock();

		$schemaFilter->expects( $this->once() )
			->method( 'filter' );

		$instance = $this->newFilterTrait();

		$instance->setNodeFilter(
			$schemaFilter
		);

		$instance->filter( $compartment );
	}

	private function newFilterTrait() {
		return new class() {

			use FilterTrait;

			public function getName() {
				return 'Foo';
			}

			protected function match( \SMW\Schema\Compartment $compartment ) {
			}
		};
	}

}
