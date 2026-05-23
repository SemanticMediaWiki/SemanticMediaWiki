<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SkinTemplate;
use SMW\MediaWiki\Hooks\PersonalUrls;
use SMW\MediaWiki\JobQueue;
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
	private $user;

	protected function setUp(): void {
		$this->skinTemplate = $this->getMockBuilder( SkinTemplate::class )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( JobQueue::class )
			->disableOriginalConstructor()
			->getMock();

		$this->userOptionsLookup = $this->getMockBuilder( UserOptionsLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->settings = $this->createMock( Settings::class );

		$this->user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PersonalUrls::class,
			new PersonalUrls( $this->jobQueue, $this->userOptionsLookup, $this->settings )
		);
	}

	public function testProcessReturnsTrueWhenUserPreferenceDisabled() {
		$this->userOptionsLookup->expects( $this->once() )
			->method( 'getOption' )
			->with( $this->user, 'smw-prefs-general-options-jobqueue-watchlist', false )
			->willReturn( false );

		$this->skinTemplate->expects( $this->any() )
			->method( 'getUser' )
			->willReturn( $this->user );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$personalUrls = [];

		$instance = new PersonalUrls(
			$this->jobQueue,
			$this->userOptionsLookup,
			$this->settings
		);

		$this->assertTrue(
			$instance->onPersonalUrls( $personalUrls, $title, $this->skinTemplate )
		);

		$this->assertArrayNotHasKey(
			'smw-jobqueue-watchlist',
			$personalUrls
		);
	}

}
