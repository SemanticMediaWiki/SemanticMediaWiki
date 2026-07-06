<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use SMW\MediaWiki\Job;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyStatisticsRebuildJob extends Job {

	/**
	 * @since 2.5
	 */
	public function __construct(
		Title $title,
		array $params,
		Store $store
	) {
		parent::__construct( 'smw.propertyStatisticsRebuild', $title, $params );
		$this->setStore( $store );
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.5
	 */
	public function run(): bool {
		if ( $this->waitOnCommandLineMode() ) {
			return true;
		}

		$deferredCallableUpdate = ApplicationFactory::getInstance()->newDeferredTransactionalCallableUpdate(
			[ $this, 'rebuild' ]
		);

		$deferredCallableUpdate->setOrigin( __METHOD__ );
		$deferredCallableUpdate->runAsAutoCommit();
		$deferredCallableUpdate->pushUpdate();

		return true;
	}

	public function rebuild(): void {
		$maintenanceFactory = ApplicationFactory::getInstance()->newMaintenanceFactory();

		// The property statistics table and its update are bound to the
		// SQLStore. The injected Store is the default Store; production
		// always wires SQLStore here, and tests inject a SQLStore mock.
		$propertyStatisticsRebuilder = $maintenanceFactory->newPropertyStatisticsRebuilder(
			$this->store
		);

		$propertyStatisticsRebuilder->rebuild();
	}

}
