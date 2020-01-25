<?php

namespace SMW\Tests;

use SMW\OptionsAwareTrait;

/**
 * @covers \SMW\OptionsAwareTrait
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class OptionsAwareTraitTest extends \PHPUnit_Framework_TestCase {

	public function testSetGetOptions() {

		$instance = $this->newOptionsAware();

		$instance->setOptions( [ 'foo' => 42, 'bar' => 1001 ] );

		$this->assertSame(
			42,
			$instance->getOption( 'foo' )
		);
	}

	public function testSetGetOption() {

		$instance = $this->newOptionsAware();

		$instance->setOption( 'foo', 42 );

		$this->assertSame(
			42,
			$instance->getOption( 'foo' )
		);
	}

	public function testIsFagSet() {

		$instance = $this->newOptionsAware();

		$instance->setOption( 'foo', SMW_QSORT | SMW_QSORT_RANDOM );

		$this->assertTrue(
			$instance->isFlagSet( 'foo', SMW_QSORT )
		);

		$this->assertFalse(
			$instance->isFlagSet( 'foo', SMW_QSORT_UNCONDITIONAL )
		);
	}

	private function newOptionsAware() {
		return new class() {

			use OptionsAwareTrait;
		};
	}

}
