<?php

namespace SMW\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SMW\Services\ServicesFactory;
use SMW\Tests\TestConfig;

/**
 * @covers \SMW\Tests\TestConfig
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.3.2
 */
class TestConfigTest extends TestCase {

	private const KEY = 'smwgTestConfigTestKey';

	protected function setUp(): void {
		parent::setUp();

		$GLOBALS[self::KEY] = 'original';
		ServicesFactory::getInstance()->getSettings()->set( self::KEY, 'original' );
	}

	protected function tearDown(): void {
		unset( $GLOBALS[self::KEY] );
		ServicesFactory::getInstance()->getSettings()->delete( self::KEY );

		parent::tearDown();
	}

	public function testResetRestoresGlobalSetMoreThanOnce(): void {
		$this->setTwiceThenReset();

		$this->assertSame( 'original', $GLOBALS[self::KEY] );
	}

	public function testResetRestoresSettingSetMoreThanOnce(): void {
		$this->setTwiceThenReset();

		$this->assertSame( 'original', ServicesFactory::getInstance()->getSettings()->get( self::KEY ) );
	}

	private function setTwiceThenReset(): void {
		$config = new TestConfig();
		$config->set( [ self::KEY => 'first' ] );
		$config->set( [ self::KEY => 'second' ] );
		$config->reset();
	}

}
