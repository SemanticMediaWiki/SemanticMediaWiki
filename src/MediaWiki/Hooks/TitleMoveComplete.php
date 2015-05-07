<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\Factbox\FactboxCache;

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

		$cache = $applicationFactory->getCache();
		$cacheFactory = $applicationFactory->newCacheFactory();

		// Delete all data for a non-enabled target NS
		if ( !$applicationFactory->getNamespaceExaminer()->isSemanticEnabled( $this->newTitle->getNamespace() ) ) {

			$cache->delete(
				$cacheFactory->getFactboxCacheKey( $this->oldId )
			);

			$applicationFactory->getStore()->deleteSubject(
				$this->oldTitle
			);

		} else {

			$settings = $applicationFactory->getSettings();

			if ( $this->newId > 0 ) {
				$cache->save(
					$cacheFactory->getPurgeCacheKey( $this->newId ),
					$settings->get( 'smwgAutoRefreshOnPageMove' )
				);
			}

			if ( $this->oldId > 0 ) {
				$cache->save(
					$cacheFactory->getPurgeCacheKey( $this->oldId ),
					$settings->get( 'smwgAutoRefreshOnPageMove' )
				);
			}

			$applicationFactory->getStore()->changeTitle(
				$this->oldTitle,
				$this->newTitle,
				$this->oldId,
				$this->newId
			);
		}

		return true;
	}

}
