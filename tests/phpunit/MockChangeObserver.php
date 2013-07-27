<?php

namespace SMW\Test;

use \SMW\ChangeObserver;
use \SMW\TitleProvider;

/**
 * MockChangeAgent should only be used during testing to establish that
 * a correct behaviour between Observer and Subject has been established.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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
