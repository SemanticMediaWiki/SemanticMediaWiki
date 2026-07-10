<?php

namespace SMW\Tests\Unit\Maintenance;

use PHPUnit\Framework\TestCase;
use SMW\DatabaseMetaRepo;
use SMW\Maintenance\AutoRecovery;

/**
 * @covers \SMW\Maintenance\AutoRecovery
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class AutoRecoveryTest extends TestCase {

	/**
	 * Per-identifier `smw_meta` row key for the 'foo' identifier used below.
	 */
	private const META_KEY = AutoRecovery::TOPIC_IDENTIFIER . '.foo';

	private $repo;

	protected function setUp(): void {
		parent::setUp();

		$this->repo = $this->createMock( DatabaseMetaRepo::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AutoRecovery::class,
			new AutoRecovery( 'Foo', $this->repo )
		);
	}

	public function testHasReadsWithoutWriting() {
		// #7030 regression guard: the read path (has) must never persist.
		// Previously `has()` created a `.smw.json` file and threw on a
		// non-writable `$smwgConfigFileDir`.
		$this->repo->method( 'readValue' )
			->with( self::META_KEY )
			->willReturn( null );

		$this->repo->expects( $this->never() )
			->method( 'writeValue' );

		$instance = new AutoRecovery( 'foo', $this->repo );
		$instance->enable( true );

		$this->assertFalse(
			$instance->has( 'ar_id' )
		);
	}

	public function testGetSet() {
		$this->repo->method( 'readValue' )
			->with( self::META_KEY )
			->willReturn( [ 'ar_id' => false ] );

		$this->repo->expects( $this->once() )
			->method( 'writeValue' )
			->with( self::META_KEY, [ 'ar_id' => 1001 ] );

		$instance = new AutoRecovery( 'foo', $this->repo );
		$instance->enable( true );
		$instance->safeMargin( 101 );

		$this->assertSame(
			false,
			$instance->get( 'ar_id' )
		);

		$instance->set( 'ar_id', 1001 );

		$this->assertEquals(
			900, // @see safeMargin
			$instance->get( 'ar_id' )
		);
	}

	public function testSetClosed() {
		$this->repo->method( 'readValue' )
			->with( self::META_KEY )
			->willReturn( [ 'ar_id' => 42 ] );

		$this->repo->expects( $this->once() )
			->method( 'writeValue' )
			->with( self::META_KEY, [ 'ar_id' => false ] );

		$instance = new AutoRecovery( 'foo', $this->repo );
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
		$this->repo->method( 'readValue' )
			->with( self::META_KEY )
			->willReturn( [ 'ar_id' => 42 ] );

		$instance = new AutoRecovery( 'foo', $this->repo );
		$instance->enable( true );
		$instance->safeMargin( 9999 );

		$this->assertSame(
			0,
			$instance->get( 'ar_id' )
		);
	}

	public function testDisabledShortCircuitsWithoutTouchingStore() {
		$this->repo->expects( $this->never() )->method( 'readValue' );
		$this->repo->expects( $this->never() )->method( 'writeValue' );

		$instance = new AutoRecovery( 'foo', $this->repo );
		// Not enabled.

		$this->assertFalse( $instance->has( 'ar_id' ) );
		$this->assertFalse( $instance->get( 'ar_id' ) );
		$this->assertFalse( $instance->set( 'ar_id', 1001 ) );
	}

}
