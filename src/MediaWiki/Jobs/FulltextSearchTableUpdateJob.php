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
class FulltextSearchTableUpdateJob extends Job {

	/**
	 * @since 2.5
	 */
	public function __construct(
		Title $title,
		array $params,
		Store $store
	) {
		parent::__construct( 'smw.fulltextSearchTableUpdate', $title, $params );
		$this->setStore( $store );
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.5
	 */
	public function run(): bool {
		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		$textChangeUpdater = $fulltextSearchTableFactory->newTextChangeUpdater(
			$this->store
		);

		$textChangeUpdater->pushUpdatesFromJobParameters(
			$this->params
		);

		MediaWikiServices::getInstance()
			->getHookContainer()
			->run( 'SMW::Job::AfterFulltextSearchTableUpdateComplete', [ $this ] );

		return true;
	}

}
