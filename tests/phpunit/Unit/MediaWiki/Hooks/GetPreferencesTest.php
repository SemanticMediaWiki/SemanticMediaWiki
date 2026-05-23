<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\HookDispatcher;
use SMW\MediaWiki\Hooks\GetPreferences;
use SMW\Schema\SchemaFactory;
use SMW\Settings;

/**
 * @covers \SMW\MediaWiki\Hooks\GetPreferences
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class GetPreferencesTest extends TestCase {

	private $hookDispatcher;
	private $schemaFactory;
	private $settings;

	protected function setUp(): void {
		parent::setUp();

		$this->hookDispatcher = $this->getMockBuilder( HookDispatcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->schemaFactory = $this->getMockBuilder( SchemaFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->settings = $this->createMock( Settings::class );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			GetPreferences::class,
			new GetPreferences( $this->schemaFactory, $this->hookDispatcher, $this->settings )
		);
	}

	/**
	 * @dataProvider keyProvider
	 */
	public function testProcess( $key ) {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();

		$preferences = [];

		$instance = new GetPreferences(
			$this->schemaFactory,
			$this->hookDispatcher,
			$this->settings
		);

		$instance->onGetPreferences( $user, $preferences );

		$this->assertArrayHasKey(
			$key,
			$preferences
		);
	}

	public function keyProvider() {
		$provider[] = [
			'smw-prefs-intro'
		];

		$provider[] = [
			'smw-prefs-ask-options-tooltip-display'
		];

		$provider[] = [
			'smw-prefs-general-options-time-correction'
		];

		$provider[] = [
			'smw-prefs-general-options-disable-editpage-info'
		];

		return $provider;
	}

}
