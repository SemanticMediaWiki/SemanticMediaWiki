<?php

namespace SMW;

/**
 * Implements the Observer interface resutling in a base class that accomodates
 * necessary methods to operate according the invoked state
 *
 * @ingroup Observer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class BaseObserver implements Observer {

	/**
	 * @since  1.9
	 *
	 * @param Observable|null $observable
	 */
	public function __construct( Observable $observable = null ) {

		if ( $observable instanceof Observable ) {
			$observable->attach( $this );
		}

	}

	/**
	 * @see Observer::update
	 *
	 * @since 1.9
	 *
	 * @param Observable|null $observable
	 */
	public function update( Observable $observable ) {

		if ( method_exists( $this, $observable->getState() ) ) {
			$this->{ $observable->getState() }( $observable->getSubject() );
		}
	}

}
