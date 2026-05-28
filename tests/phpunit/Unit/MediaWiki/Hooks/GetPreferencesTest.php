<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\GroupPermissions;
use SMW\MediaWiki\Hooks\GetPreferences;
use SMW\MediaWiki\PermissionManager;
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

	private $hookContainer;
	private $schemaFactory;
	private $settings;
	private $permissionManager;

	protected function setUp(): void {
		parent::setUp();

		$this->hookContainer = $this->createMock( HookContainer::class );
		$this->schemaFactory = $this->createMock( SchemaFactory::class );
		$this->settings = $this->createMock( Settings::class );
		$this->permissionManager = $this->createMock( PermissionManager::class );
	}

	private function newInstance(): GetPreferences {
		return new GetPreferences(
			$this->schemaFactory,
			$this->hookContainer,
			$this->settings,
			$this->permissionManager
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			GetPreferences::class,
			$this->newInstance()
		);
	}

	/**
	 * @dataProvider keyProvider
	 */
	public function testProcess( $key ) {
		// Grant only the permission the production code is expected to check
		// (`VIEW_JOBQUEUE_WATCHLIST`). A blanket `willReturn( true )` would not
		// detect a regression that replaced this constant with a different one.
		$this->permissionManager->method( 'userHasRight' )
			->willReturnCallback(
				static fn ( $user, $right ): bool => $right === GroupPermissions::VIEW_JOBQUEUE_WATCHLIST
			);

		$user = $this->createMock( User::class );
		$preferences = [];

		$this->newInstance()->onGetPreferences( $user, $preferences );

		$this->assertArrayHasKey( $key, $preferences );
	}

	public function keyProvider() {
		return [
			[ 'smw-prefs-intro' ],
			[ 'smw-prefs-ask-options-tooltip-display' ],
			[ 'smw-prefs-general-options-time-correction' ],
			[ 'smw-prefs-general-options-disable-editpage-info' ],
			[ 'smw-prefs-general-options-jobqueue-watchlist' ],
		];
	}

}
