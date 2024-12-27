<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\PersonalUrls;

/**
 * @covers \SMW\MediaWiki\Hooks\PersonalUrls
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class PersonalUrlsTest extends \PHPUnit\Framework\TestCase {

	private $skinTemplate;
	private $jobQueue;
	private $permissionExaminer;
	private $preferenceExaminer;

	protected function setUp(): void {
		$this->skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();

		$this->permissionExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Permission\PermissionExaminer' )
			->disableOriginalConstructor()
			->getMock();

		$this->preferenceExaminer = $this->getMockBuilder( '\SMW\MediaWiki\Preference\PreferenceExaminer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PersonalUrls::class,
			new PersonalUrls( $this->skinTemplate, $this->jobQueue, $this->permissionExaminer, $this->preferenceExaminer )
		);
	}

	public function testProcessOnJobQueueWatchlist() {
		$this->preferenceExaminer->expects( $this->at( 0 ) )
			->method( 'hasPreferenceOf' )
			->with( 'smw-prefs-general-options-jobqueue-watchlist' )
			->willReturn( true );

		$output = $this->getMockBuilder( '\OutputPage' )
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
			$this->preferenceExaminer
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
