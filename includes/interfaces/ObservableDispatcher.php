<?php

namespace SMW;

/**
 * Extends the Observable interface to reset the Subject
 *
 * An ObservableDispatcher enables an emitter (client) of an event not being
 * directly linked to an Observer, freeing it from implementing methods that are
 * necessary to communicate with it while maintaining a capability to transmitt
 * state changes to Observers that are registered with a dispatcher
 *
 * @ingroup Observer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface ObservableDispatcher extends Observable {

	/**
	 * Specifies the source from which events are transmitted and emitted
	 *
	 * @since  1.9
	 *
	 * @param DispatchableSubject $subject
	 */
	public function setObservableSubject( DispatchableSubject $subject );

}