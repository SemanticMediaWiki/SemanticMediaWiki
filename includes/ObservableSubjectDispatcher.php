<?php

namespace SMW;

/**
 * Dispatches notifification (state changes) from a client to registered
 * Observers
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface describing a Subject that is dispatchable
 *
 * @ingroup Observer
 */
interface DispatchableSubject {

	/**
	 * Forwards requests to a ObservableDispatcher
	 *
	 * @since  1.9
	 *
	 * @param ObservableDispatcher $dispatcher
	 */
	public function setObservableDispatcher( ObservableDispatcher $dispatcher );

}

/**
 * Extends the Observable interface to forward the source
 *
 * This ObservableDispatcher enables the emitter (client) of an event not being
 * directly linked to an Observer, freeing it from implementing methods to
 * communicate with it while maintaining capabability to transmitt state changes
 * to Observers that are registered with this dispatcher.
 *
 * @ingroup Observer
 */
interface ObservableDispatcher extends Observable {

	/**
	 * Specifies the source from which events are transmitted
	 *
	 * @since  1.9
	 *
	 * @param mixed $subject
	 */
	public function setSubject( DispatchableSubject $subject );

}

/**
 * Implementation of the ObservableDispatcher
 *
 * ObservableDispatcher inherits all methods from ObservableSubject in order to
 * communicate with its Observers.
 *
 * @par Example:
 * @code
 *  $changeNotifier = new PropertyChangeNotifier( ... );
 *  $changeNotifier->setObservableDispatcher( new ObservableSubjectDispatcher( new ChangeObserver() ) );
 * @endcode
 *
 * @ingroup Observer
 */
class ObservableSubjectDispatcher extends ObservableSubject implements ObservableDispatcher {

	/** @var mixed */
	protected $subject = null;

	/**
	 * Registeres a DispatchableSubject
	 *
	 * @since 1.9
	 *
	 * @param $subject
	 *
	 * @return ObservableSubjectDispatcher
	 */
	public function setSubject( DispatchableSubject $subject ) {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * Overrides ObservableSubject::getSource to ensure that the emitting client
	 * is identified as source and not the ObservableDispatcher, in order for
	 * the Observer to act on the clients behalf
	 *
	 * @since 1.9
	 *
	 * @return mixed
	 */
	public function getSubject() {
		return $this->subject;
	}

}
