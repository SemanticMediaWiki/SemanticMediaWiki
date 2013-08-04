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
 * Interface describing a source that is dispatchable
 *
 * @ingroup Observer
 */
interface DispatchableSource {

	/**
	 * Frowards requests fo
	 *
	 * @since  1.9
	 *
	 * @param Dispatchable $dispatcher
	 */
	public function setDispatcher( ObservableDispatcher $dispatcher );

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
	 * @param mixed $source
	 */
	public function setSource( $source );

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
 *  $changeNotifier->setDispatcher( new ObservableSubjectDispatcher( new ChangeObserver() ) );
 * @endcode
 *
 * @ingroup Observer
 */
class ObservableSubjectDispatcher extends ObservableSubject implements ObservableDispatcher {

	/** @var mixed */
	protected $source = null;

	/**
	 * Registeres the DispatchableSource
	 *
	 * @since 1.9
	 *
	 * @param $source
	 *
	 * @return ObservableSubjectDispatcher
	 */
	public function setSource( $source ) {
		$this->source = $source;
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
	public function getSource() {
		return $this->source;
	}

}
