<?php

namespace SMW\Tests\Integration\MediaWiki\Jobs;

use MapCacheLRU;
use MediaWiki\Title\Title;
use RuntimeException;
use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use SMW\Listener\EventListener\EventHandler;
use SMW\Services\ServicesFactory;
use SMW\Tests\SMWIntegrationTestCase;
use Wikimedia\ObjectCache\HashBagOStuff;

/**
 * Needs the database and MediaWiki's services, as the job parses the page and updates the store.
 *
 * @covers \SMW\MediaWiki\Jobs\UpdateJob
 * @covers \SMW\DataUpdater
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class UpdateJobEntityCacheTest extends SMWIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Its listeners hold the services of the test that first dispatched an event,
		// including an EntityCache other than the one this test reads
		EventHandler::clear();
	}

	protected function tearDown(): void {
		// So that later tests do not get listeners holding this test's EntityCache
		EventHandler::clear();

		parent::tearDown();
	}

	public function testLeavesNoCachedValueOfThePageWhenTheUpdateFailsAfterSaving(): void {
		$title = $this->getExistingTestPage( 'UpdateJobEntityCachePage' )->getTitle();
		$entityCache = ServicesFactory::getInstance()->getEntityCache();
		$entityCache->getWithSetCallback( $title, 'probe', EntityCache::TTL_HOUR, static fn () => 'cached' );

		$this->setTemporaryHook( 'SMW::Store::AfterDataUpdateComplete', static function (): void {
			throw new RuntimeException( 'Failed after saving' );
		} );

		$this->assertSame( 'Failed after saving', $this->runUpdateJobUntilItFails( $title ) );
		$this->assertFalse( $entityCache->fetch( $title, 'probe' ) );
	}

	public function testClearsTheCachedValuesOfThePageOnce(): void {
		$entityCache = $this->newEntityCacheCountingInvalidations();
		$this->setService( 'SMW.EntityCache', $entityCache );
		$title = $this->getExistingTestPage( 'UpdateJobEntityCachePage' )->getTitle();
		$entityCache->invalidations = [];

		$this->runUpdateJob( $title );

		$this->assertSame( 1, $entityCache->invalidations[WikiPage::newFromTitle( $title )->getHash()] ?? 0 );
	}

	private function runUpdateJob( Title $title ): void {
		ServicesFactory::getInstance()->newJobFactory()->newUpdateJob( $title )->run();
	}

	/**
	 * @return string The message of the exception that stopped the job
	 */
	private function runUpdateJobUntilItFails( Title $title ): string {
		try {
			$this->runUpdateJob( $title );
		} catch ( RuntimeException $e ) {
			return $e->getMessage();
		}

		return 'Not failed';
	}

	private function newEntityCacheCountingInvalidations(): EntityCache {
		return new class( new HashBagOStuff(), new MapCacheLRU( 1000 ) ) extends EntityCache {

			/**
			 * @var array<string,int> The number of invalidations by page hash
			 */
			public array $invalidations = [];

			public function invalidateMulti( array $subjects ): void {
				foreach ( $subjects as $subject ) {
					$page = $subject instanceof Title ? WikiPage::newFromTitle( $subject ) : $subject;
					$hash = $page->asBase()->getHash();
					$this->invalidations[$hash] = ( $this->invalidations[$hash] ?? 0 ) + 1;
				}

				parent::invalidateMulti( $subjects );
			}

		};
	}

}
