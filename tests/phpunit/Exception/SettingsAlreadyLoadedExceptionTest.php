<?php

namespace SMW\Tests\Exception;

use SMW\Exception\SettingsAlreadyLoadedException;

/**
 * @covers \SMW\Exception\SettingsAlreadyLoadedException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class SettingsAlreadyLoadedExceptionTest extends \PHPUnit\Framework\TestCase {

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
