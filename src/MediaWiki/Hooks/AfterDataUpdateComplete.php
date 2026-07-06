<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DataModel\SemanticData;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\QueryDependencyLinksStoreFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\Store;

/**
 * Runs after a property table data update has completed; updates the query
 * dependency store and the fulltext-search index.
 *
 * @see https://www.semantic-mediawiki.org/wiki/Hooks#SMW::SQLStore::AfterDataUpdateComplete
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class AfterDataUpdateComplete {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly QueryDependencyLinksStoreFactory $queryDependencyLinksStoreFactory,
		private readonly FulltextSearchTableFactory $fulltextSearchTableFactory,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onSMW__SQLStore__AfterDataUpdateComplete( Store $store, SemanticData $semanticData, ChangeOp $changeOp ): bool {
		// A delete infused change should trigger an immediate update
		// without having to wait on the job queue
		$isPrimaryUpdate = $semanticData->getOption( SemanticData::PROC_DELETE, false );

		$queryDependencyLinksStore = $this->queryDependencyLinksStoreFactory->newQueryDependencyLinksStore(
			$store
		);

		$queryDependencyLinksStore->pruneOutdatedTargetLinks(
			$changeOp
		);

		$textChangeUpdater = $this->fulltextSearchTableFactory->newTextChangeUpdater(
			$store
		);

		$textChangeUpdater->isPrimary( $isPrimaryUpdate );

		$textChangeUpdater->pushUpdates(
			$changeOp
		);

		return true;
	}

}
