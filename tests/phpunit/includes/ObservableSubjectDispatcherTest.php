<?php

namespace SMW\Test;

use SMW\ObservableSubjectDispatcher;
use SMW\ObservableDispatcher;

/**
 * Tests for the Observer/Subject pattern
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\Observer
 * @covers \SMW\ObservableSubject
 * @covers \SMW\ObservableSubjectDispatcher
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ObservableSubjectDispatcherTest extends SemanticMediaWikiTestCase {

	protected $subject = null;

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ObservableSubjectDispatcher';
	}

	/**
	 * Helper method that returns a DispatchableSubject object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return DispatchableSubject
	 */
	private function newDispatchableSubject() {

		$source = $this->getMockBuilder( '\SMW\DispatchableSubject' )
			->setMethods( array( 'setObservableDispatcher' ) )
			->getMock();

		$source->expects( $this->any() )
			->method( 'setObservableDispatcher' )
			->will( $this->returnCallback( array( $this, 'setObservableDispatcherCallback' ) ) );

		return $source;

	}

	/**
	 * Helper method that returns a Observer object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return Observer
	 */
	private function newObserver() {

		$observer = $this->getMockBuilder( '\SMW\Observer' )
			->setMethods( array( 'lila' ) )
			->getMock();

		$observer->expects( $this->any() )
			->method( 'lila' )
			->will( $this->returnCallback( array( $this, 'lilaObserverCallback' ) ) );

		return $observer;

	}

	/**
	 * Helper method that returns a ObservableSubjectDispatcher object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return ObservableSubjectDispatcher
	 */
	private function getInstance( $observer = null ) {
		return new ObservableSubjectDispatcher( $observer );

	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testAttachDetach() {

		$dispatcher = $this->getInstance();

		$observer = $this->newObserver();
		$dispatcher->attach( $observer );

		$this->assertCount( 1, $dispatcher->getObservers() );
		$dispatcher->detach( $observer );
		$this->assertCount( 0, $dispatcher->getObservers() );

		$dispatcher = $this->getInstance( $this->newObserver() );
		$this->assertCount( 1, $dispatcher->getObservers() );

	}

	/**
	 * @since 1.9
	 */
	public function testSourceDispatching() {

		// Register Observer with the dispatcher
		$dispatcher = $this->getInstance( $this->newObserver() );

		// Register dispatcher with a source
		$source = $this->newDispatchableSubject();
		$source->setObservableDispatcher( $dispatcher );

		// Rather being the source itself, the dispatcher returns the invoked instance
		$this->assertInstanceOf( '\SMW\DispatchableSubject', $this->subject );
		$this->assertEquals( $this->subject, $dispatcher->getSubject() );

		// The dipatchers forwards a registered state change but the Observer
		// is using the invoked source ($this) as means to communicate
		$dispatcher->setState( 'lila' );
		$this->assertEquals( 'lilaObserver was informed by source', $this->subject->ObserverSentAMessage );

	}

	/**
	 * Verifies that the Observer was acting on the invoked Subject
	 *
	 * @since 1.9
	 *
	 * @param $subject
	 */
	public function lilaObserverCallback( $subject ) {
		return $subject->ObserverSentAMessage = 'lilaObserver was informed by source';
	}

	/**
	 * Sets the source
	 *
	 * @since 1.9
	 *
	 * @param ObservableDispatcher $dispatcher
	 */
	public function setObservableDispatcherCallback( ObservableDispatcher $dispatcher ) {

		$this->subject = $this->getMockBuilder( '\SMW\DispatchableSubject' )
			->setMethods( array( 'setObservableDispatcher' ) )
			->getMock();

		$dispatcher->setSubject( $this->subject );
	}

}
