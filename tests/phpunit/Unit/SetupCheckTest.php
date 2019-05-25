<?php

namespace SMW\Tests;

use SMW\SetupCheck;
use SMW\Utils\File;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\SetupCheck
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SetupCheckTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SetupCheck::class,
			new SetupCheck( [] )
		);
	}

	public function testHasError() {

		$vars = [];
		$instance = new SetupCheck( [] );

		$this->assertInternalType(
			'boolean',
			$instance->hasError( $vars )
		);
	}

	public function testGetError() {

		$vars = [];
		$instance = new SetupCheck(
			[
				'wgScriptPath' => 'foo'
			]
		);

		$this->assertInternalType(
			'string',
			$instance->getError( true, $vars )
		);
	}

}
