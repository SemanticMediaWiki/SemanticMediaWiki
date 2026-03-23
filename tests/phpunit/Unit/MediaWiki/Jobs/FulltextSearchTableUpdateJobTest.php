<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableUpdateJobTest extends TestCase {

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->testEnvironment->registerObject(
			'Store',
			$this->getMockBuilder( SQLStore::class )->getMockForAbstractClass()
		);
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			FulltextSearchTableUpdateJob::class,
			new FulltextSearchTableUpdateJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testJobRun( $parameters ) {
		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = new FulltextSearchTableUpdateJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function parametersProvider() {
		return [
			[
				'diff' => [
					'slot:id' => 'itemName#123#extraData',
					1,
					2
				]
			],
			[
				'diff' => [
					'slot:id' => 'itemName#123#extraData#additionalInfo',
					1,
					2
				]
			],
			[
				'diff' => [
					1,
					2
				]
			]
		];
	}

}
