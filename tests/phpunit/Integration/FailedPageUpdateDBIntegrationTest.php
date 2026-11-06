<?php

namespace SMW\Tests\Integration;

use JobQueue;
use LogicException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\Services\ServicesFactory;
use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;
use Wikimedia\Rdbms\DBError;

/**
 * Needs the database and the job queue: the behaviour is what a page's stored data is once its
 * update failed and was repeated.
 *
 * @covers \SMW\DataUpdater
 * @covers \SMW\SQLStore\SQLStoreUpdater
 * @group semantic-mediawiki-integration
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 */
class FailedPageUpdateDBIntegrationTest extends SMWIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment->addConfiguration( 'smwgEnableUpdateJobs', true );
		$this->updateJobQueue()->delete();
	}

	public function testDataOfAFailedPageUpdateIsStoredByTheQueuedJob(): void {
		$this->failNextUpdateOf( 'Failed_update_retry' );
		$this->editPageIgnoringFailure( 'Failed_update_retry', '[[Failed update retry value::Stored later]]' );

		$this->runJobs( [ 'minJobs' => 0 ], [ 'type' => 'smw.update' ] );

		$this->assertSame( 'Stored_later', $this->storedValueOf( 'Failed_update_retry', 'Failed_update_retry_value' ) );
	}

	public function testFailedUpdateJobFailsInsteadOfQueuingAnotherJob(): void {
		$this->editPage( 'Failed_update_job', '[[Failed update job value::Value]]' );
		$this->failNextUpdateOf( 'Failed_update_job' );

		$this->assertSame(
			[ 'failed' => true, 'queued jobs' => 0 ],
			[ 'failed' => $this->runUpdateJobFails( 'Failed_update_job' ), 'queued jobs' => $this->updateJobQueue()->getSize() ]
		);
	}

	public function testRepeatingAFailedUpdateInTheSameProcessStoresItsData(): void {
		$semanticData = new SemanticData( new WikiPage( 'Failed_update_repeated', NS_MAIN ) );
		$semanticData->addPropertyObjectValue(
			new Property( 'Failed_update_repeated_value' ),
			new WikiPage( 'Stored_later', NS_MAIN )
		);

		$this->failNextUpdateOf( 'Failed_update_repeated' );
		$this->updateDataIgnoringFailure( $semanticData );
		$this->getStore()->updateData( $semanticData );

		$this->assertSame( 'Stored_later', $this->storedValueOf( 'Failed_update_repeated', 'Failed_update_repeated_value' ) );
	}

	private function failNextUpdateOf( string $page ): void {
		$failed = false;

		$this->setTemporaryHook(
			'SMW::SQLStore::AfterDataUpdateComplete',
			static function ( SQLStore $store, SemanticData $semanticData ) use ( $page, &$failed ): void {
				if ( $failed || $semanticData->getSubject()->getDBkey() !== $page ) {
					return;
				}

				// Failing after the update committed would not test a failed update
				if ( !$store->getConnection( 'mw.db' )->inSectionTransaction( SQLStore::UPDATE_TRANSACTION ) ) {
					throw new LogicException( 'The hook no longer runs inside the page update transaction' );
				}

				$failed = true;
				throw new DBError( null, 'Record has changed since last read' );
			},
			false
		);
	}

	private function editPageIgnoringFailure( string $page, string $text ): void {
		try {
			$this->editPage( $page, $text );
		} catch ( DBError ) {
		}
	}

	private function updateDataIgnoringFailure( SemanticData $semanticData ): void {
		try {
			$this->getStore()->updateData( $semanticData );
		} catch ( DBError ) {
		}
	}

	private function runUpdateJobFails( string $page ): bool {
		$updateJob = ServicesFactory::getInstance()->newJobFactory()->newUpdateJob(
			Title::newFromText( $page ),
			[ UpdateJob::FORCED_UPDATE => true ]
		);

		try {
			$updateJob->run();
		} catch ( DBError ) {
			return true;
		}

		return false;
	}

	/**
	 * Clears the store's in-process cache first, so a cached value cannot hide one missing from the
	 * database.
	 */
	private function storedValueOf( string $page, string $property ): ?string {
		$this->getStore()->clear();
		$values = $this->getStore()->getPropertyValues( new WikiPage( $page, NS_MAIN ), new Property( $property ) );

		return $values === [] ? null : $values[0]->getDBkey();
	}

	private function updateJobQueue(): JobQueue {
		return MediaWikiServices::getInstance()->getJobQueueGroup()->get( 'smw.update' );
	}

}
