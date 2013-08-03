<?php

namespace SMW;

/**
 * General purpose change agent to enforce loose coupling by having
 * a Publisher (subject) sent a change notification to this observer
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * General purpose change agent to enforce loose coupling by having
 * a Publisher (subject) sent a change notification to this observer
 *
 * @note When testing rountrips, use MockChangeObserver instead
 *
 * @ingroup Observer
 * @ingroup Utility
 */
class ChangeObserver extends Observer implements Cacheable, Configurable, StoreAccess {

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
	 * UpdateJob dispatching
	 *
	 * loading of data
	 *
	 * Generally by the time the job is execute the store has been updated and
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

		$dispatcher = new PropertySubjectsUpdateDispatcherJob( $subject->getTitle() );
		$dispatcher->setSettings( $this->getSettings() );

		if ( $this->getSettings()->get( 'smwgDeferredPropertyUpdate' ) ) {
			$dispatcher->insert(); // JobQueue is handling dispatching
		} else {
			$dispatcher->run();
		}

		return true;
	}

}
