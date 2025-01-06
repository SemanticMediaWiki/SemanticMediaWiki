<?php

namespace SMW\Tests\Elastic;

use SMW\Elastic\Installer;

/**
 * @covers \SMW\Elastic\Installer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class InstallerTest extends \PHPUnit\Framework\TestCase {

	private $rollover;

	protected function setUp(): void {
		$this->rollover = $this->getMockBuilder( '\SMW\Elastic\Indexer\Rebuilder\Rollover' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Installer::class,
			new Installer( $this->rollover )
		);
	}

	public function testNewSetupFile() {
		$instance = new Installer( $this->rollover );

		$this->assertInstanceOf(
			'\SMW\SetupFile',
			$instance->newSetupFile()
		);
	}

	public function testSetup() {
		$this->rollover->expects( $this->exactly( 2 ) )
			->method( 'update' );

		$instance = new Installer(
			$this->rollover
		);

		$instance->setup();
	}

	public function testDrop() {
		$this->rollover->expects( $this->exactly( 2 ) )
			->method( 'delete' );

		$instance = new Installer(
			$this->rollover
		);

		$instance->drop();
	}

	public function testRollover() {
		$this->rollover->expects( $this->once() )
			->method( 'rollover' )
			->willReturn( 'foo' );

		$instance = new Installer(
			$this->rollover
		);

		$instance->rollover( 'foo', 'v1' );
	}

}
