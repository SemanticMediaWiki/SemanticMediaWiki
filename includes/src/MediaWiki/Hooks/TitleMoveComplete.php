<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;

/**
 * TitleMoveComplete occurs whenever a request to move an article
 * is completed
 *
 * This method will be called whenever an article is moved so that
 * semantic properties are moved accordingly.
 *
 * @see http://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
 *
 * @ingroup FunctionHook
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

		/**
		 * @var Settings $settings
		 */
		$settings = ApplicationFactory::getInstance()->getSettings();

		/**
		 * @var CacheHandler $cache
		 */
		$cache = ApplicationFactory::getInstance()->getCache();

		$cache->setCacheEnabled( $this->newId > 0 )
			->setKey( ArticlePurge::newCacheId( $this->newId ) )
			->set( $settings->get( 'smwgAutoRefreshOnPageMove' ) );

		$cache->setCacheEnabled( $this->oldId > 0 )
			->setKey( ArticlePurge::newCacheId( $this->oldId ) )
			->set( $settings->get( 'smwgAutoRefreshOnPageMove' ) );

		ApplicationFactory::getInstance()->getStore()->changeTitle(
			$this->oldTitle,
			$this->newTitle,
			$this->oldId,
			$this->newId
		);

		return true;
	}

}
