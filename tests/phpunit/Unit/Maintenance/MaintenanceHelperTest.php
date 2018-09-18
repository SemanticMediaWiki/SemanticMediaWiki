<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\MaintenanceHelper;

/**
 * @covers \SMW\Maintenance\MaintenanceHelper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MaintenanceHelperTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Maintenance\MaintenanceHelper',
			new MaintenanceHelper()
		);
	}

	public function testSetGlobalForValidKey() {

		$GLOBALS['FOObar'] = 42;

		$instance = new MaintenanceHelper();
		$instance->setGlobalToValue( 'FOObar', 99 );

		$this->assertEquals(
			$GLOBALS['FOObar'],
			99
		);

		$instance->reset();

		$this->assertEquals(
			$GLOBALS['FOObar'],
			42
		);

		unset( $GLOBALS['FOObar'] );
	}

	public function testTrySetGlobalForInvalidKey() {

		$instance = new MaintenanceHelper();
		$instance->setGlobalToValue( 'FOObar', 99 );

		$this->assertFalse(
			isset( $GLOBALS['FOObar'] )
		);
	}

	/**
	 * @dataProvider runtimeKeyValueProvider
	 */
	public function testRuntimeMonitor( $runtimeKey ) {

		$instance = new MaintenanceHelper();

		$this->assertInternalType(
			'array',
			$instance->getRuntimeValues()
		);

		$instance->initRuntimeValues();

		$this->assertArrayHasKey(
			$runtimeKey,
			$instance->getRuntimeValues()
		);

		$instance->reset();
	}

	public function testTransformRuntimeValuesForOutput() {

		$instance = new MaintenanceHelper();
		$instance->initRuntimeValues();

		$this->assertContains(
			'sec',
			$instance->getFormattedRuntimeValues()
		);

		$instance->reset();
	}

	public function runtimeKeyValueProvider() {

		$provider = [
			[ 'time' ],
			[ 'memory-before' ],
			[ 'memory-after' ],
			[ 'memory-used' ]
		];

		return $provider;
	}

}
