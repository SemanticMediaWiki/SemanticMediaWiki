<?php

namespace SMW;

/**
 * Implementation of the ObservableDispatcher
 *
 * ObservableSubjectDispatcher inherits all methods from an ObservableSubject
 * in order to communicate with registered Observers
 *
 * @par Example:
 * @code
 *  $notifier = new PropertyTypeComparator( ... );
 *  $notifier->registerDispatcher(
 *     new ObservableSubjectDispatcher( new UpdateObserver() )
 *  );
 * @endcode
 *
 * @ingroup Observer
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ObservableSubjectDispatcher extends ObservableSubject implements ObservableDispatcher {

	/** @var mixed */
	protected $subject = null;

	/**
	 * @see ObservableDispatcher::setObservableSubject
	 *
	 * @since 1.9
	 *
	 * @param DispatchableSubject $subject
	 *
	 * @return ObservableSubjectDispatcher
	 */
	public function setObservableSubject( DispatchableSubject $subject ) {
		$this->subject = $subject;
		return $this;
	}

	/**
	 * @see ObservableSubject::getSubject
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
