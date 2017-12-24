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
class PersonalUrlsTest extends \PHPUnit_Framework_TestCase {

	private $skinTemplate;
	private $jobQueue;

	protected function setUp() {

		$this->skinTemplate = $this->getMockBuilder( '\SkinTemplate' )
			->disableOriginalConstructor()
			->getMock();

		$this->jobQueue = $this->getMockBuilder( '\SMW\MediaWiki\JobQueue' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PersonalUrls::class,
			new PersonalUrls( $this->skinTemplate, $this->jobQueue )
		);
	}

	public function testProcessOnJobQueueWatchlist() {

		$output = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$this->skinTemplate->expects( $this->any() )
			->method( 'getOutput' )
			->will( $this->returnValue( $output ) );

		$personalUrls = [];

		$instance = new PersonalUrls(
			$this->skinTemplate,
			$this->jobQueue
		);

		$instance->setOptions(
			[
				'smwgJobQueueWatchlist' => [ 'Foo' ],
				'prefs-jobqueue-watchlist' => true
			]
		);

		$instance->process( $personalUrls );

		$this->assertArrayHasKey(
			'smw-jobqueue-watchlist',
			$personalUrls
		);
	}

}
