<?php

namespace SMW\Tests\Integration\MediaWiki\Jobs;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\FulltextRebuildFixtureTrait;

/**
 * @covers \SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableRebuildJobTest extends SMWIntegrationTestCase {

	use FulltextRebuildFixtureTrait;

	protected function setUp(): void {
		parent::setUp();

		$this->enableFulltextSearch();
	}

	private function newJob( Title $title, array $params = [] ): FulltextSearchTableRebuildJob {
		/** @var FulltextSearchTableRebuildJob $job */
		$job = MediaWikiServices::getInstance()->getJobFactory()->newJob(
			'smw.fulltextSearchTableRebuild',
			$title,
			$params
		);
		return $job;
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			FulltextSearchTableRebuildJob::class,
			$this->newJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testRunJob( $parameters ) {
		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = $this->newJob( $subject->getTitle(), $parameters );

		$this->assertTrue(
			$instance->run()
		);
	}

	public function testChunkJobRebuildsItsChunkAndQueuesTheNext() {
		$this->clearQueuedChunks();
		[ $sid, $pid ] = $this->storeTexts( 'ChunkJobSubject', 'Has chunked text', [ 'alphaword' ] );
		$this->flushIndex();
		// Keeps the rebuild from ending with this chunk
		$this->writeIndexEntry( $sid + 1000, $pid, 'farword' );

		$this->newJob( WikiPage::newFromText( __METHOD__ )->getTitle(), [ 's' => $sid, 'n' => 1 ] )->run();

		$this->assertStringContainsString( 'alphaword', $this->readIndexEntry( $sid, $pid ) );
		$this->assertSame( [ [ 's' => $sid + 1, 'n' => 1 ] ], $this->queuedChunks() );
	}

	public function testChunkJobForTheLastChunkQueuesNoFurtherJob() {
		$this->clearQueuedChunks();
		$this->storeTexts( 'ChunkJobSubject', 'Has chunked text', [ 'alphaword' ] );
		$this->flushIndex();

		$this->newJob(
			WikiPage::newFromText( __METHOD__ )->getTitle(),
			[ 's' => $this->maxSubjectId(), 'n' => 1 ]
		)->run();

		$this->assertSame( [], $this->queuedChunks() );
	}

	public function parametersProvider() {
		$provider[] = [
			[]
		];

		$provider[] = [
			[ 'table' => 'Foo' ]
		];

		$provider[] = [
			[ 'mode' => 'full' ]
		];

		$provider[] = [
			[ 'mode' => 'chunked', 'n' => 0 ]
		];

		$provider[] = [
			[ 'mode' => 'chunked', 's' => 0, 'n' => 250 ]
		];

		$provider[] = [
			[ 'mode' => 'chunked', 's' => 1000, 'n' => 250 ]
		];

		return $provider;
	}
}
