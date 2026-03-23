<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
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
class SettingNotFoundExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new SettingNotFoundException();

		$this->assertInstanceof(
			SettingNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
