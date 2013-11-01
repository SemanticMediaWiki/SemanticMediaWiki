<?php

namespace SMW\Test;

use \SMW\UpdateObserver;
use \SMW\TitleAccess;
use \SMW\ParserData;

/**
 * MockUpdateObserver should only be used during testing to establish that
 * a correct behaviour between Observer and Subject has been established.
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * MockUpdateObserver should only be used during testing to establish that
 * a correct behaviour between Observer and Subject has been established.
 *
 * Use the setNotifier/getNotifier to verify the function that was expected
 * during processing was invoked because of a notification sent by the
 * Publisher(Subject).
 *
 * Testing the publisher does not involve testing the execution of
 * the expected processor and is part of a different test which should be
 * treated as independent unit test.
 *
 * @par Example:
 * @code
 *  $observer = new MockUpdateObserver();
 *  $instance->attach( $observer );
 *
 *  $this->assertEquals( 'runFoo', $observer->getNotifier() )
 * @end
 *
 * @ingroup Observer
 * @codeCoverageIgnore
 */
class MockUpdateObserver extends UpdateObserver {

	/** @var string */
	protected $notifier = null;

	/**
	 * Sets a notifier
	 *
	 * Verfifies that the expected notifier is actually available in the
	 * parent class, meaning that any unrecognized renaming of any method
	 * in the mock or parent class will be flagged by returning null
	 * instead of the expected notifier name.
	 *
	 * @since 1.9
	 */
	private function setNotifier( $notifier ) {
		$this->notifier = method_exists( get_parent_class( $this ), $notifier ) ? $notifier : null;
	}

	/**
	 * Returns a registered notifier
	 *
	 * @since 1.9
	 */
	public function getNotifier() {
		return $this->notifier;
	}

	/**
	 * @see UpdateObserver::runUpdateDispatcher
	 *
	 * @since 1.9
	 *
	 * @param TitleAccess $subject
	 */
	public function runUpdateDispatcher( TitleAccess $subject ) {
		$this->setNotifier( __FUNCTION__ );
	}

	/**
	 * @see UpdateObserver::runStoreUpdater
	 *
	 * @since 1.9
	 *
	 * @param ParserData $subject
	 */
	public function runStoreUpdater( ParserData $subject ) {
		$this->setNotifier( __FUNCTION__ );
	}

}
