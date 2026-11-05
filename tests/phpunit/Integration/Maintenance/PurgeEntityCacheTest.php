<?php

namespace SMW\Tests\Integration\Maintenance;

use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use SMW\Services\ServicesFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\TestEnvironment;

/**
 * Runs the script against the database, because it reads the entities to purge from the entity table.
 *
 * @covers \SMW\Maintenance\purgeEntityCache
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class PurgeEntityCacheTest extends SMWIntegrationTestCase {

	private $runnerFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->runnerFactory = TestEnvironment::getUtilityFactory()->newRunnerFactory();
	}

	public function testInvalidatesTheCachedValuesOfEachEntity(): void {
		$subject = $this->storeEntity( 'PurgeCacheEntity' );
		$this->lookUp( $subject, 'outdated' );

		$this->runScript();

		$this->assertSame( 'regenerated', $this->lookUp( $subject, 'regenerated' ) );
	}

	public function testInvalidatesEntitiesBeyondTheFirstBatch(): void {
		// Three entities in batches of two: one of them is neither the first nor the last row of its batch
		$alpha = $this->storeEntity( 'PurgeCacheAlpha' );
		$beta = $this->storeEntity( 'PurgeCacheBeta' );
		$gamma = $this->storeEntity( 'PurgeCacheGamma' );
		$this->lookUp( $alpha, 'outdated' );
		$this->lookUp( $beta, 'outdated' );
		$this->lookUp( $gamma, 'outdated' );

		$this->runScript( [ 'batch-size' => 2 ] );

		$this->assertSame(
			[ 'regenerated', 'regenerated', 'regenerated' ],
			[
				$this->lookUp( $alpha, 'regenerated' ),
				$this->lookUp( $beta, 'regenerated' ),
				$this->lookUp( $gamma, 'regenerated' )
			]
		);
	}

	private function runScript( array $options = [] ): void {
		$maintenanceRunner = $this->runnerFactory->newMaintenanceRunner(
			'\SMW\Maintenance\purgeEntityCache'
		);

		$maintenanceRunner->setOptions( $options );
		$maintenanceRunner->setQuiet();

		$maintenanceRunner->run();
	}

	private function storeEntity( string $page ): WikiPage {
		$this->getStore()->getObjectIds()->makeSMWPageID( $page, NS_MAIN, '', '' );

		return WikiPage::newFromText( $page );
	}

	private function lookUp( WikiPage $subject, string $generatedValue ): mixed {
		$entityCache = ServicesFactory::getInstance()->getEntityCache();

		return $entityCache->getWithSetCallback(
			$subject,
			$entityCache->makeKey( 'test', $subject->getHash() ),
			EntityCache::TTL_WEEK,
			static fn () => $generatedValue
		);
	}

}
