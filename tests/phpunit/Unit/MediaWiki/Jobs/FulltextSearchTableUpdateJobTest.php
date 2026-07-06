<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Jobs\FulltextSearchTableUpdateJob;
use SMW\SQLStore\SQLStore;
use SMW\Store;

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

	private function newStore(): Store {
		return $this->getMockBuilder( SQLStore::class )->getMockForAbstractClass();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			FulltextSearchTableUpdateJob::class,
			new FulltextSearchTableUpdateJob( $title, [], $this->newStore() )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testJobRun( $parameters ) {
		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = new FulltextSearchTableUpdateJob(
			$subject->getTitle(),
			$parameters,
			$this->newStore()
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
