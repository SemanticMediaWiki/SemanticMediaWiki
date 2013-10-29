<?php

namespace SMW;

/**
 * Interface describing a Subject that is dispatchable
 *
 * @ingroup Observer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface DispatchableSubject {

	/**
	 * Register an ObservableDispatcher
	 *
	 * This allows requests to be forwarded to Observers registered with an
	 * ObservableDispatcher
	 *
	 * @since  1.9
	 *
	 * @param ObservableDispatcher $dispatcher
	 */
	public function registerDispatcher( ObservableDispatcher $dispatcher );

}