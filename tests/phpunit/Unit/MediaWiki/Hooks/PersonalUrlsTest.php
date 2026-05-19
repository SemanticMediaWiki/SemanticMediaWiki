<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use MediaWiki\Output\OutputPage;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\PersonalUrls;
use SMW\MediaWiki\Permission\PermissionExaminer;

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
	private $permissionExaminer;
	private $userOptionsLookup;
	private $user;

	protected function setUp(): void {
		$this->skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( PermissionExaminer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->userOptionsLookup = $this->getMockBuilder( UserOptionsLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PersonalUrls::class,
			new PersonalUrls( $this->skinTemplate, $this->jobQueue, $this->permissionExaminer, $this->userOptionsLookup, $this->user )
		);
	}

	public function testProcessOnJobQueueWatchlist() {
		$this->userOptionsLookup->expects( $this->once() )
			->method( 'getOption' )
			->with( $this->user, 'smw-prefs-general-options-jobqueue-watchlist', false )
			->willReturn( true );

		$output = $this->getMockBuilder( OutputPage::class )
			->disableOriginalConstructor()
			->getMock();

		$this->skinTemplate->expects( $this->any() )
			->method( 'getOutput' )
			->willReturn( $output );

		$this->permissionExaminer->expects( $this->any() )
			->method( 'hasPermissionOf' )
			->willReturn( true );

		$personalUrls = [];

		$instance = new PersonalUrls(
			$this->skinTemplate,
			$this->jobQueue,
			$this->permissionExaminer,
			$this->userOptionsLookup,
			$this->user
		);

		$instance->setOptions(
			[
				'smwgJobQueueWatchlist' => [ 'Foo' ]
			]
		);

		$instance->process( $personalUrls );

		$this->assertArrayHasKey(
			'smw-jobqueue-watchlist',
			$personalUrls
		);
	}

}
