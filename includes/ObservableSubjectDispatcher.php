<?php

namespace SMW;

/**
 * Dispatches state changes from a client to registered Observers
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
	 * Invokes an ObservableDispatcher
	 *
	 * This allows requests to be forwarded to Observers registered with an
	 * ObservableDispatcher
	 *
	 * @since  1.9
	 *
	 * @param ObservableDispatcher $dispatcher
	 */
	public function setObservableDispatcher( ObservableDispatcher $dispatcher );

}

/**
 * Extends the Observable interface to reset the Subject
 *
 * An ObservableDispatcher enables an emitter (client) of an event not being
 * directly linked to an Observer, freeing it from implementing methods that are
 * necessary to communicate with it while maintaining a capability to transmitt
 * state changes to Observers that are registered with a dispatcher
 *
 * @ingroup Observer
 */
interface ObservableDispatcher extends Observable {

	/**
	 * Specifies the source from which events are transmitted
	 *
	 * @since  1.9
	 *
	 * @param DispatchableSubject $subject
	 */
	public function setSubject( DispatchableSubject $subject );

}

/**
 * Implementation of the ObservableDispatcher
 *
 * ObservableSubjectDispatcher inherits all methods from an ObservableSubject
 * in order to communicate with registered Observers
 *
 * @par Example:
 * @code
 *  $changeNotifier = new PropertyChangeNotifier( ... );
 *  $changeNotifier->setObservableDispatcher(
 *     new ObservableSubjectDispatcher( new UpdateObserver() )
 *  );
 * @endcode
 *
 * @ingroup Observer
 */
class ObservableSubjectDispatcher extends ObservableSubject implements ObservableDispatcher {

	/** @var mixed */
	protected $subject = null;

	/**
	 * Registers a DispatchableSubject
	 *
	 * @since 1.9
	 *
	 * @param DispatchableSubject $subject
	 *
	 * @return ObservableSubjectDispatcher
	 */
	public function setSubject( DispatchableSubject $subject ) {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * @see ObservableSubject::getSource
	 *
	 * @note Returns a subject to ensure that the emitting client is identified
	 * as source and not the ObservableDispatcher, in order for the Observer to
	 * act on the clients behalf
	 *
	 * @since 1.9
	 *
	 * @return mixed
	 */
	public function getSubject() {
		return $this->subject;
	}

}
