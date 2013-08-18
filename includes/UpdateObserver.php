<?php

namespace SMW;

/**
 * Observer for independent update transactions
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Observer for independent update transactions
 *
 * Using this observer can help to enforce loose coupling by having
 * a Publisher (ObservableSubject) sent a notification (state change)
 * to this observer which will independently act from the source of
 * the notification
 *
 * @note When testing rountrips, use the MockUpdateObserver instead
 *
 * @ingroup Observer
 */
class UpdateObserver extends Observer implements Cacheable, Configurable, StoreAccess {

	/** @var Settings */
	protected $settings = null;

	/** @var Store */
	protected $store = null;

	/** @var CacheHandler */
	protected $cache = null;

	/**
	 * Sets Store object
	 *
	 * @since 1.9
	 *
	 * @param Store $store
	 */
	public function setStore( Store $store ) {
		$this->store = $store;
	}

	/**
	 * Returns Store object
	 *
	 * @since 1.9
	 *
	 * @return Store
	 */
	public function getStore() {

		if ( $this->store === null ) {
			$this->store = StoreFactory::getStore();
		}

		return $this->store;
	}

	/**
	 * Sets Settings object
	 *
	 * @since 1.9
	 *
	 * @param Settings $settings
	 *
	 * @return ChangeAgent
	 */
	public function setSettings( Settings $settings ) {
		$this->settings = $settings;
		return $this;
	}

	/**
	 * Returns Settings object
	 *
	 * @since 1.9
	 *
	 * @return Settings
	 */
	public function getSettings() {

		if ( $this->settings === null ) {
			$this->settings = Settings::newFromGlobals();
		}

		return $this->settings;
	}

	/**
	 * Returns CacheHandler object
	 *
	 * @since 1.9
	 *
	 * @return CacheHandler
	 */
	public function getCache() {

		if ( $this->cache === null ) {
			$this->cache = CacheHandler::newFromId( $this->getSettings()->get( 'smwgCacheType' ) );
		}

		return $this->cache;
	}

	/**
	 * Store updater
	 *
	 * @note Is called from UpdateJob::run, LinksUpdateConstructed::process, and
	 * ParserAfterTidy::process
	 *
	 * @since 1.9
	 *
	 * @param ParserData $subject
	 *
	 * @return true
	 */
	public function runStoreUpdater( ParserData $subject ) {

		$updater = new StoreUpdater( $this->getStore(), $subject->getData(), $this->getSettings() );
		$updater->setUpdateStatus( $subject->getUpdateStatus() )->doUpdate();

		return true;
	}

	/**
	 * UpdateJob dispatching
	 *
	 * Normally by the time the job is execute the store has been updated and
	 * data that belong to a property potentially are no longer are associate
	 * with a subject.
	 *
	 * [1] Immediate dispatching influences the performance during page saving
	 * since data that belongs to the property are loaded directly from the DB
	 * which has direct impact about the response time of the page in question.
	 *
	 * [2] Deferred dispatching uses a different approach to recognize necessary
	 * objects involved and deferres property/pageIds mapping to the JobQueue.
	 * This makes it unnecessary to load data from the DB therefore decrease
	 * performance degration during page update.
	 *
	 * @since 1.9
	 *
	 * @param TitleAccess $subject
	 */
	public function runUpdateDispatcher( TitleAccess $subject ) {

		$dispatcher = new UpdateDispatcherJob( $subject->getTitle() );
		$dispatcher->setSettings( $this->getSettings() );

		if ( $this->getSettings()->get( 'smwgDeferredPropertyUpdate' ) && class_exists( '\SMW\PropertyPageIdMapper' ) ) {
			$dispatcher->insert(); // JobQueue is handling dispatching
		} else {
			$dispatcher->run();
		}

		return true;
	}

}
