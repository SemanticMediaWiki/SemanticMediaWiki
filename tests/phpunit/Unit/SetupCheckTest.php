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

		$setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();

		$setupFile->expects( $this->any() )
			->method( 'inMaintenanceMode' )
			->will( $this->returnValue( true ) );

		$instance = new SetupCheck( [], $setupFile );

		$this->assertInternalType(
			'boolean',
			$instance->hasError()
		);
	}

	public function testGetError() {

		$setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new SetupCheck(
			[
				'wgScriptPath' => 'foo'
			],
			$setupFile
		);

		$this->assertInternalType(
			'string',
			$instance->getError( true )
		);
	}

}
