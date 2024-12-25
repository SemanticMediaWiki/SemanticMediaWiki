<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\MaintenanceHelper;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Maintenance\MaintenanceHelper
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class MaintenanceHelperTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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
			99,
			$GLOBALS['FOObar']
		);

		$instance->reset();

		$this->assertEquals(
			42,
			$GLOBALS['FOObar']
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

		$this->assertIsArray(

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
