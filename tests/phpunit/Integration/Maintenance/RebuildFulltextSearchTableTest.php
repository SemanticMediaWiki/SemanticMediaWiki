<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\FulltextRebuildFixtureTrait;

/**
 * Runs the script against the database, because it reads and writes the full-text index table.
 *
 * @covers \SMW\Maintenance\rebuildFulltextSearchTable
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class RebuildFulltextSearchTableTest extends SMWIntegrationTestCase {

	use FulltextRebuildFixtureTrait;

	private $runnerFactory;
	private $spyMessageReporter;

	protected function setUp(): void {
		parent::setUp();

		$this->enableFulltextSearch();
		$this->runnerFactory  = $this->testEnvironment::getUtilityFactory()->newRunnerFactory();
		$this->spyMessageReporter = $this->testEnvironment::getUtilityFactory()->newSpyMessageReporter();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testRun() {
		$this->runScript( [] );

		$this->assertStringContainsString(
			'script is used to rebuild or optimize the search index',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testRunInChunks() {
		$this->runScript( [
			'n' => 250,
			's' => 0,
			'max-time' => 60,
			'quick' => true
		] );

		// Message from RebuildFulltextSearchTable::execute()
		$this->assertStringContainsString(
			'It is rebuilt in chunks of 250 subject IDs',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testDefaultRunStoresSortKeysAsPageSavesDo() {
		[ $sid, $pid ] = $this->storeTexts( 'DefaultRunSortKey', 'Has chunked text', [ 'alphaword' ] );
		$this->flushIndex();

		$this->runScript( [ 'quick' => true ] );

		$this->assertSame( 'alphaword', $this->readIndexSortKey( $sid, $pid ) );
	}

	public function testDefaultRunRemovesEntriesBeyondTheLastSubject() {
		[ , $pid ] = $this->storeTexts( 'DefaultRunStale', 'Has chunked text', [ 'alphaword' ] );
		$staleSid = $this->maxSubjectId() + 1000;
		$this->writeIndexEntry( $staleSid, $pid, 'staleword' );

		$this->runScript( [ 'quick' => true ] );

		$this->assertFalse( $this->readIndexEntry( $staleSid, $pid ) );
	}

	public function testStartOptionOnlyRebuildsFromThatSubjectOnwards() {
		[ $earlierSid, $pid ] = $this->storeTexts( 'ChunkedScriptEarlier', 'Has chunked text', [ 'alphaword' ] );
		[ $startSid ] = $this->storeTexts( 'ChunkedScriptStart', 'Has chunked text', [ 'bravoword' ] );
		$this->flushIndex();

		$this->runScript( [
			's' => $startSid,
			'quick' => true
		] );

		$this->assertFalse( $this->readIndexEntry( $earlierSid, $pid ) );
		$this->assertStringContainsString( 'bravoword', $this->readIndexEntry( $startSid, $pid ) );
	}

	public function testInsertJobIntoJobQueueInsteadOfRebuilding() {
		$this->clearQueuedChunks();

		$this->runScript( [
			'use-job' => true,
			'n' => 250,
			's' => 0,
			'quick' => true
		] );

		// Message from RebuildFulltextSearchTable::queueRebuildJob()
		$this->assertStringContainsString(
			'... queued (s=0, n=250)',
			$this->spyMessageReporter->getMessagesAsString()
		);

		$this->assertSame(
			[ [ 's' => 0, 'n' => 250 ] ],
			$this->queuedChunks()
		);
	}

	private function runScript( array $options ): void {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\rebuildFulltextSearchTable'
		);

		$maintenanceRunner->setMessageReporter( $this->spyMessageReporter );
		$maintenanceRunner->setOptions( $options );
		$maintenanceRunner->setQuiet();

		$maintenanceRunner->run();
	}

}
