<?php

namespace SMW\Tests\Exception;

use SMW\Exception\SettingsAlreadyLoadedException;

/**
 * @covers \SMW\Exception\SettingsAlreadyLoadedException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SettingsAlreadyLoadedExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new SettingsAlreadyLoadedException();

		$this->assertInstanceof(
			SettingsAlreadyLoadedException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
