<?php

namespace SMW;

/**
 * Contains interfaces and implementation classes to
 * enable a Observer-Subject (or Publisher-Subcriber) pattern where
 * objects can indepentanly be notfied about a state change and initiate
 * an update of its registered Publisher
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Implement the Subsriber interface resutling in an Observer base class
 * that accomodates necessary methods to update an invoked publisher
 *
 * @ingroup Observer
 */
abstract class Observer implements Subscriber {

	/**
	 * @since  1.9
	 *
	 * @param Publisher|null $subject
	 */
	public function __construct( Publisher $subject = null ) {
		if ( $subject instanceof Publisher ) {
			$subject->attach( $this );
		}
	}

	/**
	 * Update handling of an invoked publisher by relying
	 * on the state object to carry out the task
	 *
	 * @since 1.9
	 *
	 * @param Publisher|null $subject
	 */
	public function update( Publisher $subject ) {

		if ( method_exists( $this, $subject->getState() ) ) {
			call_user_func_array( array( $this, $subject->getState() ), array( $subject ) );
		}
	}
}
