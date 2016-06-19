<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\Factbox\FactboxCache;
use SMW\EventHandler;

/**
 * TitleMoveComplete occurs whenever a request to move an article
 * is completed
 *
 * This method will be called whenever an article is moved so that
 * semantic properties are moved accordingly.
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class TitleMoveComplete {

	/**
	 * @var Title
	 */
	protected $oldTitle = null;

	/**
	 * @var Title
	 */
	protected $newTitle = null;

	/**
	 * @var User
	 */
	protected $user = null;

	/**
	 * @var integer
	 */
	protected $oldId;

	/**
	 * @var integer
	 */
	protected $newId;

	/**
	 * @since  1.9
	 *
	 * @param Title $oldTitle old title
	 * @param Title $newTitle: new title
	 * @param Use $user user who did the move
	 * @param $oldId database ID of the page that's been moved
	 * @param $newId database ID of the created redirect
	 */
	public function __construct( &$oldTitle, &$newTitle, &$user, $oldId, $newId ) {
		$this->oldTitle = $oldTitle;
		$this->newTitle = $newTitle;
		$this->user = $user;
		$this->oldId = $oldId;
		$this->newId = $newId;
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {

		$applicationFactory = ApplicationFactory::getInstance();

		// Delete all data for a non-enabled target NS
		if ( !$applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $this->newTitle->getNamespace() ) || $this->newId == 0 ) {

			$applicationFactory->getStore()->deleteSubject(
				$this->oldTitle
			);

		} else {

		// Using a different approach since the hook is not triggered
		// by #REDIRECT which can cause inconsistencies
		// @see 2.3 / StoreUpdater

		//	$applicationFactory->getStore()->changeTitle(
		//		$this->oldTitle,
		//		$this->newTitle,
		//		$this->oldId,
		//		$this->newId
		//	);
		}

		$eventHandler = EventHandler::getInstance();

		$dispatchContext = $eventHandler->newDispatchContext();
		$dispatchContext->set( 'title', $this->oldTitle );

		$eventHandler->getEventDispatcher()->dispatch(
			'cached.propertyvalues.prefetcher.reset',
			$dispatchContext
		);

		$dispatchContext = $eventHandler->newDispatchContext();
		$dispatchContext->set( 'title', $this->newTitle );

		$eventHandler->getEventDispatcher()->dispatch(
			'cached.propertyvalues.prefetcher.reset',
			$dispatchContext
		);

		return true;
	}

}
