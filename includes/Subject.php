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
 * Implement the Publisher interface resulting in an Subject base class
 *
 * @ingroup Observer
 */
abstract class Subject implements Publisher {

	/** @var Subscriber[] */
	protected $observers = array();

	/** string */
	protected $state = null;

	/**
	 * @see Publisher::attach
	 *
	 * @since 1.9
	 *
	 * @param Subscriber $observer
	 */
	public function attach( Subscriber $observer ) {
		if ( $this->contains( $observer ) === null ) {
			$this->observers[] = $observer;
		}
	}

	/**
	 * @since  1.9
	 *
	 * @param Subscriber $observer
	 */
	public function detach( Subscriber $observer ) {
		$index = $this->contains( $observer );
		if ( $index !== null ) {
			unset( $this->observers[$index] );
		}
	}

	/**
	 * Returns a string which represents an updateable
	 * Publisher object method
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getState() {
		return $this->state;
	}

	/**
	 * Set a state variable state and initiates to notify
	 * the attached Subscribers
	 *
	 * @since 1.9
	 *
	 * @param string $state
	 */
	public function setState( $state ) {
		$this->state = $state;
		$this->notify();
	}

	/**
	 * Notifies the updater of all invoked Subscribers
	 *
	 * @since  1.9
	 */
	public function notify() {
		foreach ( $this->observers as $observer ) {
			$observer->update( $this );
		}
	}

	/**
	 * Returns registered Observers
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getObservers() {
		return $this->observers;
	}

	/**
	 * Returns an index (or null) of a registered Observer
	 *
	 * @since  1.9
	 *
	 * @param Subscriber $observer
	 *
	 * @return integer|null
	 */
	protected function contains( Subscriber $observer ) {
		foreach ( $this->observers as $key => $obs ) {
			if ( $obs === $observer ) {
				return $key;
			}
		}
		return null;
	}

}
