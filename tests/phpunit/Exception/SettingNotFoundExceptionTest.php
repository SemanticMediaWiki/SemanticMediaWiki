<?php

namespace SMW\Tests\Exception;

use SMW\Exception\SettingNotFoundException;

/**
 * @covers \SMW\Exception\SettingNotFoundException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SettingNotFoundExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new SettingNotFoundException();

		$this->assertInstanceof(
			'\SMW\Exception\SettingNotFoundException',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
