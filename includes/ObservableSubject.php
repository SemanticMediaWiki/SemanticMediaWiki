<?php

namespace SMW;

/**
 * Implements the Pubisher/Observable interface as base class
 *
 * @note In the GOF book this class/interface is known as Subject
 *
 * @note We will avoid referring to it as Subject otherwise it could be
 * mistaken with Semantic MediaWiki's own "Subject" (DIWikiPage) object
 *
 * @ingroup Observer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
abstract class ObservableSubject implements Observable {

	/** @var Observer[] */
	protected $observers = array();

	/** string */
	protected $state = null;

	/**
	 * @since  1.9
	 *
	 * @param Observer|null $subject
	 */
	public function __construct( Observer $observer = null ) {

		if ( $observer instanceof Observer ) {
			$this->attach( $observer );
		}

	}

	/**
	 * @see Observable::attach
	 *
	 * @since 1.9
	 *
	 * @param Observer $observer
	 */
	public function attach( Observer $observer ) {

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
	 * @param Observer $observer
	 */
	public function detach( Observer $observer ) {

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
	 * @see Observable::getSubject
	 *
	 * @since 1.9
	 *
	 * @return Observable
	 */
	public function getSubject() {
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
	 * @param Observer $observer
	 *
	 * @return integer|null
	 */
	private function contains( Observer $observer ) {

		foreach ( $this->observers as $key => $obs ) {
			if ( $obs === $observer ) {
				return $key;
			}
		}

		return null;
	}

}
