<?php

namespace SMW;

/**
 * Interface and abstract class defining the operations for
 * attaching and de-attaching observers
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Base Publisher interface
 *
 * @ingroup Observer
 */
interface Publisher {

	/**
	 * Attaches a Subscriber
	 *
	 * @since  1.9
	 *
	 * @param Subscriber $subscriber
	 */
	public function attach( Subscriber $subscriber );

	/**
	 * Detaches a Subscriber
	 *
	 * @since  1.9
	 *
	 * @param Subscriber $subscriber
	 */
	public function detach( Subscriber $subscriber );

	/**
	 * Notifies attached Subscribers
	 *
	 * @since  1.9
	 */
	public function notify();

}

/**
 * Extended Publisher interface specifying access to
 * source and state changes
 *
 * @ingroup Observer
 */
interface Observable extends Publisher {

	/**
	 * Returns the invoked state change
	 *
	 * @since 1.9
	 */
	public function getState();

	/**
	 * Registers a state change
	 *
	 * @since 1.9
	 */
	public function setState( $state );

	/**
	 * Returns the emitter of the state change
	 *
	 * @since 1.9
	 */
	public function getSource();

}

/**
 * Implements the Pubisher/Observable interface as base class
 *
 * @note In the GOF book this class/interface is known as Subject
 *
 * @note We will avoid referring to it as Subject otherwise it could be
 * mistaken with Semantic MediaWiki's own "Subject" (DIWikiPage) object
 *
 * @ingroup Observer
 */
abstract class ObservableSubject implements Observable {

	/** @var Subscriber[] */
	protected $observers = array();

	/** string */
	protected $state = null;

	/**
	 * @since  1.9
	 *
	 * @param Subscriber|null $subject
	 */
	public function __construct( Subscriber $observer = null ) {
		if ( $observer instanceof Subscriber ) {
			$this->attach( $observer );
		}
	}

	/**
	 * @see Observable::attach
	 *
	 * @since 1.9
	 *
	 * @param Subscriber $observer
	 */
	public function attach( Subscriber $observer ) {

		if ( $this->contains( $observer ) === null ) {
			$this->observers[] = $observer;
		}

		return $this;
	}

	/**
	 * @see Observable::attach
	 *
	 * @since  1.9
	 *
	 * @param Subscriber $observer
	 */
	public function detach( Subscriber $observer ) {

		$index = $this->contains( $observer );

		if ( $index !== null ) {
			unset( $this->observers[$index] );
		}

		return $this;
	}

	/**
	 * @see Observable::getState
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
	 * the attached Subscribers (Observers)
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
	 * @see Observable::getSource
	 *
	 * @since 1.9
	 *
	 * @return Observable
	 */
	public function getSource() {
		return $this;
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
	private function contains( Subscriber $observer ) {

		foreach ( $this->observers as $key => $obs ) {
			if ( $obs === $observer ) {
				return $key;
			}
		}

		return null;
	}

}
