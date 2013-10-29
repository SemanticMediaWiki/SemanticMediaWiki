<?php

namespace SMW;

/**
 * Extended Publisher interface specifying access to
 * source and state changes
 *
 * @ingroup Observer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface Observable {

	/**
	 * Attaches a Subscriber
	 *
	 * @since  1.9
	 *
	 * @param Observer $observer
	 */
	public function attach( Observer $observer );

	/**
	 * Detaches a Subscriber
	 *
	 * @since  1.9
	 *
	 * @param Observer $observer
	 */
	public function detach( Observer $observer );

	/**
	 * Notifies attached Subscribers
	 *
	 * @since  1.9
	 */
	public function notify();

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
	public function getSubject();

}