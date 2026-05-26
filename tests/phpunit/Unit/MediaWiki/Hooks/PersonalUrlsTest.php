<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SkinTemplate;
use SMW\GroupPermissions;
use SMW\MediaWiki\Hooks\PersonalUrls;
use SMW\MediaWiki\JobQueue;
use SMW\MediaWiki\PermissionManager;
use SMW\Settings;

/**
 * @covers \SMW\MediaWiki\Hooks\PersonalUrls
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class PersonalUrlsTest extends TestCase {

	private $skinTemplate;
	private $jobQueue;
	private $userOptionsLookup;
	private $settings;
	private $permissionManager;
	private $user;

	protected function setUp(): void {
		$this->skinTemplate = $this->createMock( SkinTemplate::class );
		$this->jobQueue = $this->createMock( JobQueue::class );
		$this->userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$this->settings = $this->createMock( Settings::class );
		$this->permissionManager = $this->createMock( PermissionManager::class );
		$this->user = $this->createMock( User::class );
	}

	private function newInstance(): PersonalUrls {
		return new PersonalUrls(
			$this->jobQueue,
			$this->userOptionsLookup,
			$this->settings,
			$this->permissionManager
		);
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PersonalUrls::class,
			$this->newInstance()
		);
	}

	public function testProcessReturnsTrueWhenUserPreferenceDisabled() {
		$this->userOptionsLookup->expects( $this->once() )
			->method( 'getOption' )
			->with( $this->user, 'smw-prefs-general-options-jobqueue-watchlist', false )
			->willReturn( false );

		$this->skinTemplate->method( 'getUser' )->willReturn( $this->user );

		$title = $this->createMock( Title::class );
		$personalUrls = [];

		$this->assertTrue(
			$this->newInstance()->onPersonalUrls( $personalUrls, $title, $this->skinTemplate )
		);

		$this->assertArrayNotHasKey( 'smw-jobqueue-watchlist', $personalUrls );
	}

	public function testProcessOnJobQueueWatchlist() {
		$this->userOptionsLookup->method( 'getOption' )
			->with( $this->user, 'smw-prefs-general-options-jobqueue-watchlist', false )
			->willReturn( true );

		$this->permissionManager->method( 'userHasRight' )
			->with( $this->user, GroupPermissions::VIEW_JOBQUEUE_WATCHLIST )
			->willReturn( true );

		$this->settings->method( 'get' )
			->with( 'smwgJobQueueWatchlist' )
			->willReturn( [ 'Foo' ] );

		$output = $this->createMock( OutputPage::class );
		$this->skinTemplate->method( 'getUser' )->willReturn( $this->user );
		$this->skinTemplate->method( 'getOutput' )->willReturn( $output );

		$title = $this->createMock( Title::class );
		$personalUrls = [];

		$this->newInstance()->onPersonalUrls( $personalUrls, $title, $this->skinTemplate );

		$this->assertArrayHasKey( 'smw-jobqueue-watchlist', $personalUrls );
	}

}
