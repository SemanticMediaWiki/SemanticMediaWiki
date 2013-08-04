<?php

namespace SMW;

/**
 * Specifies interface and abstract class for operations that are used to execute
 * registered operations
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface describing a Subsriber
 *
 * @ingroup Observer
 */
interface Subscriber {

	/**
	 * Receive update from a publishable source
	 *
	 * @since  1.9
	 *
	 * @param Observable $source
	 */
	public function update( Observable $source );

}

/**
 * Implements the Subsriber interface resutling in an Observer base class
 * that accomodates necessary methods to operate according the invoked state
 *
 * @ingroup Observer
 */
abstract class Observer implements Subscriber {

	/**
	 * @since  1.9
	 *
	 * @param Observable|null $subject
	 */
	public function __construct( Observable $subject = null ) {

		if ( $subject instanceof Observable ) {
			$subject->attach( $this );
		}

	}

	/**
	 * Operates according the invoked state and source
	 *
	 * @since 1.9
	 *
	 * @param Observable|null $subject
	 */
	public function update( Observable $subject ) {

		if ( method_exists( $this, $subject->getState() ) ) {
			call_user_func_array(
				array( $this, $subject->getState() ),
				array( $subject->getSource() )
			);
		}
	}

}
