<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\AutoRecovery;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Maintenance\AutoRecovery
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class AutoRecoveryTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $file;
	private $site;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->site = \SMW\Site::id();

		$this->file = $this->getMockBuilder( '\SMW\Utils\File' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AutoRecovery::class,
			new AutoRecovery( 'Foo' )
		);
	}

	public function testCheckForID() {
		$contents = [
			$this->site => [ 'maintenance_script.auto_recovery' => [ 'foo' => [ 'ar_id' => false ] ] ]
		];

		$this->file->expects( $this->atLeastOnce() )
			->method( 'write' )
			->with(
				$this->anything(),
				json_encode( $contents, JSON_PRETTY_PRINT ) );

		$this->file->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( false );

		$instance = new AutoRecovery( 'foo', $this->file );
		$instance->enable( true );

		$this->assertFalse(
			$instance->has( 'ar_id' )
		);
	}

	public function testGetSet() {
		$init = [
			$this->site => [ 'maintenance_script.auto_recovery' => [ 'foo' => [ 'ar_id' => false ] ] ]
		];

		$this->file->expects( $this->atLeastOnce() )
			->method( 'read' )
			->willReturn( json_encode( $init ) );

		$contents = [
			$this->site => [ 'maintenance_script.auto_recovery' => [ 'foo' => [ 'ar_id' => 1001 ] ] ]
		];

		$this->file->expects( $this->atLeastOnce() )
			->method( 'write' )
			->with(
				$this->anything(),
				json_encode( $contents, JSON_PRETTY_PRINT ) );

		$this->file->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$instance = new AutoRecovery( 'foo', $this->file );
		$instance->enable( true );
		$instance->safeMargin( 101 );

		$this->assertSame(
			0,
			$instance->get( 'ar_id' )
		);

		$instance->set( 'ar_id', 1001 );

		$this->assertEquals(
			900, // @see safeMargin
			$instance->get( 'ar_id' )
		);
	}

	public function testSetClosed() {
		$init = [
			$this->site => [ 'maintenance_script.auto_recovery' => [ 'foo' => [ 'ar_id' => 42 ] ] ]
		];

		$this->file->expects( $this->atLeastOnce() )
			->method( 'read' )
			->willReturn( json_encode( $init ) );

		$contents = [
			$this->site => [ 'maintenance_script.auto_recovery' => [ 'foo' => [ 'ar_id' => false ] ] ]
		];

		$this->file->expects( $this->atLeastOnce() )
			->method( 'write' )
			->with(
				$this->anything(),
				json_encode( $contents, JSON_PRETTY_PRINT ) );

		$this->file->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$instance = new AutoRecovery( 'foo', $this->file );
		$instance->enable( true );

		$this->assertEquals(
			42,
			$instance->get( 'ar_id' )
		);

		$instance->set( 'ar_id', false );

		$this->assertFalse(
						$instance->get( 'ar_id' )
		);
	}

	public function testGetSafeMargin() {
		$init = [
			$this->site => [ 'maintenance_script.auto_recovery' => [ 'foo' => [ 'ar_id' => 42 ] ] ]
		];

		$this->file->expects( $this->atLeastOnce() )
			->method( 'read' )
			->willReturn( json_encode( $init, JSON_PRETTY_PRINT ) );

		$this->file->expects( $this->atLeastOnce() )
			->method( 'exists' )
			->willReturn( true );

		$instance = new AutoRecovery( 'foo', $this->file );
		$instance->enable( true );
		$instance->safeMargin( 9999 );

		$this->assertSame(
			0,
			$instance->get( 'ar_id' )
		);
	}

}
