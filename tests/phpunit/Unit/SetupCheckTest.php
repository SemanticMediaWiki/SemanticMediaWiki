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

	private $setupFile;

	protected function setUp() {
		parent::setUp();

		$this->setupFile = $this->getMockBuilder( '\SMW\SetupFile' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SetupCheck::class,
			new SetupCheck( [] )
		);

		$this->assertInstanceOf(
			SetupCheck::class,
			SetupCheck::newFromDefaults()
		);
	}

	public function testHasError() {

		$this->setupFile->expects( $this->any() )
			->method( 'inMaintenanceMode' )
			->will( $this->returnValue( true ) );

		$instance = new SetupCheck( [], $this->setupFile );

		$this->assertInternalType(
			'boolean',
			$instance->hasError()
		);
	}

	public function testGetError() {

		$instance = new SetupCheck(
			[
				'wgScriptPath' => 'foo'
			],
			$this->setupFile
		);

		$this->assertInternalType(
			'string',
			$instance->getError( true )
		);
	}

}
