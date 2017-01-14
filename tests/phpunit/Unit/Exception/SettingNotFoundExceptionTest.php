<?php

namespace SMW\Tests\Exception;

use SMW\Exception\SettingNotFoundException;

/**
 * @covers \SMW\Exception\SettingNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SettingNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

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
