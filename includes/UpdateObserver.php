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
class UpdateObserver extends Observer implements DependencyRequestor {

	/** @var DependencyBuilder */
	protected $dependencyBuilder = null;

	/**
	 * @see DependencyRequestor::setDependencyBuilder
	 *
	 * @since 1.9
	 *
	 * @param DependencyBuilder $builder
	 */
	public function setDependencyBuilder( DependencyBuilder $builder ) {
		$this->dependencyBuilder = $builder;
	}

	/**
	 * @see DependencyRequestor::getDependencyBuilder
	 *
	 * @since 1.9
	 *
	 * @return DependencyBuilder
	 */
	public function getDependencyBuilder() {

		// This is not as clean as it should be but to avoid to make
		// multipe changes at once we determine a default builder here
		// which at some point should vanish after pending changes have
		// been merged

		// LinksUpdateConstructed is injecting the builder via the setter
		// UpdateJob does not
		// ParserAfterTidy does not

		if ( $this->dependencyBuilder === null ) {
			$this->dependencyBuilder = new SimpleDependencyBuilder( new SharedDependencyContainer() );
		}

		return $this->dependencyBuilder;
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

		$updater = new StoreUpdater(
			$this->getDependencyBuilder()->newObject( 'Store' ),
			$subject->getData(),
			$this->getDependencyBuilder()->newObject( 'Settings' )
		);

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

		$settings   = $this->getDependencyBuilder()->newObject( 'Settings' );

		$dispatcher = new UpdateDispatcherJob( $subject->getTitle() );
		$dispatcher->setSettings( $settings );

		if ( $settings->get( 'smwgDeferredPropertyUpdate' ) && class_exists( '\SMW\PropertyPageIdMapper' ) ) {
			// Enable coverage after PropertyPageIdMapper is available
			// @codeCoverageIgnoreStart
			$dispatcher->insert(); // JobQueue is handling dispatching
			// @codeCoverageIgnoreEnd
		} else {
			$dispatcher->run();
		}

		return true;
	}

}
