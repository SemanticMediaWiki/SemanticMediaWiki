<?php

namespace SMW\MediaWiki\Jobs;

use SMW\MediaWiki\Job;
use Hooks;
use SMW\ApplicationFactory;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableUpdateJob extends Job {

	/**
	 * @since 2.5
	 *
	 * @param Title $title
	 * @param array $params job parameters
	 */
	public function __construct( Title $title, $params = [] ) {
		parent::__construct( 'smw.fulltextSearchTableUpdate', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * @see Job::run
	 *
	 * @since  2.5
	 */
	public function run() {

		$fulltextSearchTableFactory = new FulltextSearchTableFactory();

		$textChangeUpdater = $fulltextSearchTableFactory->newTextChangeUpdater(
			ApplicationFactory::getInstance()->getStore( '\SMW\SQLStore\SQLStore' )
		);

		$textChangeUpdater->pushUpdatesFromJobParameters(
			$this->params
		);

		Hooks::run( 'SMW::Job::AfterFulltextSearchTableUpdateComplete', [ $this ] );

		return true;
	}

}
