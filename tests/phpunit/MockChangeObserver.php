<?php

namespace SMW\Test;

use \SMW\ChangeObserver;
use \SMW\TitleProvider;

/**
 * MockChangeAgent should only be used during testing to establish that
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
 * MockChangeObserver should only be used during testing to establish that
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
 *  $observer = new MockChangeObserver();
 *  $instance->attach( $observer );
 *
 *  $this->assertEquals( 'runFoo', $observer->getNotifier() )
 * @end
 *
 * @ingroup Observer
 * @codeCoverageIgnore
 */
class MockChangeObserver extends ChangeObserver {

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
	public function setNotifier( $notifier ) {
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
	 * @see ChangeObserver::runUpdateDispatcher
	 *
	 * @since 1.9
	 */
	public function runUpdateDispatcher( TitleProvider $subject ) {
		$this->setNotifier( __FUNCTION__ );
	}

}
