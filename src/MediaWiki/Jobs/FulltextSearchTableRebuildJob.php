<?php

namespace SMW\MediaWiki\Jobs;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use SMW\MediaWiki\Job;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableRebuildJob extends Job {

	/**
	 * @since 2.5
	 */
	public function __construct(
		Title $title,
		array $params,
		Store $store
	) {
		parent::__construct( 'smw.fulltextSearchTableRebuild', $title, $params );
		$this->setStore( $store );
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

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		$searchTableRebuilder = $fulltextSearchTableFactory->newSearchTableRebuilder(
			$this->store
		);

		if ( $this->hasParameter( 'table' ) ) {
			$searchTableRebuilder->rebuildByTable( $this->getParameter( 'table' ) );
		} elseif ( $this->hasParameter( 'mode' ) && $this->getParameter( 'mode' ) === 'full' ) {
			$searchTableRebuilder->rebuild();
		} else {
			$searchTableRebuilder->flushTable();
			$this->createJobsFromTableList( $searchTableRebuilder->getQualifiedTableList() );
		}

		return true;
	}

	private function createJobsFromTableList( array $tableList ): void {
		if ( $tableList === [] ) {
			return;
		}

		$jobFactory = MediaWikiServices::getInstance()->getJobFactory();

		foreach ( $tableList as $tableName ) {
			$job = $jobFactory->newJob(
				'smw.fulltextSearchTableRebuild',
				$this->getTitle(),
				[ 'table' => $tableName ]
			);

			$job->insert();
		}
	}

}
