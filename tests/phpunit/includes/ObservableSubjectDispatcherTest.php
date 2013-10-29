<?php

namespace SMW\Test;

use SMW\ObservableSubjectDispatcher;
use SMW\ObservableDispatcher;

/**
 * @covers \SMW\BaseObserver
 * @covers \SMW\ObservableSubject
 * @covers \SMW\ObservableSubjectDispatcher
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class ObservableSubjectDispatcherTest extends SemanticMediaWikiTestCase {

	protected $subject = null;

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ObservableSubjectDispatcher';
	}

	/**
	 * @since 1.9
	 *
	 * @return DispatchableSubject
	 */
	private function newDispatchableSubject() {

		$source = $this->getMockBuilder( '\SMW\DispatchableSubject' )
			->setMethods( array( 'registerDispatcher' ) )
			->getMock();

		$source->expects( $this->any() )
			->method( 'registerDispatcher' )
			->will( $this->returnCallback( array( $this, 'registerDispatcherCallback' ) ) );

		return $source;

	}

	/**
	 * @since 1.9
	 *
	 * @return Observer
	 */
	private function newObserver() {

		return $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'lila' => array( $this, 'lilaObserverCallback' )
		) );

	}

	/**
	 * @since 1.9
	 *
	 * @return ObservableSubjectDispatcher
	 */
	private function newInstance( $observer = null ) {
		return new ObservableSubjectDispatcher( $observer );

	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testAttachDetach() {

		$dispatcher = $this->newInstance();

		$observer = $this->newObserver();
		$dispatcher->attach( $observer );

		$this->assertCount( 1, $dispatcher->getObservers() );
		$dispatcher->detach( $observer );
		$this->assertCount( 0, $dispatcher->getObservers() );

		$dispatcher = $this->newInstance( $this->newObserver() );
		$this->assertCount( 1, $dispatcher->getObservers() );

	}

	/**
	 * @since 1.9
	 */
	public function testSourceDispatching() {

		// Register Observer with the dispatcher
		$dispatcher = $this->newInstance( $this->newObserver() );

		// Register dispatcher with a source
		$source = $this->newDispatchableSubject();
		$source->registerDispatcher( $dispatcher );

		// Rather being the source itself, the dispatcher returns the invoked instance
		$this->assertInstanceOf( '\SMW\DispatchableSubject', $this->subject );
		$this->assertEquals( $this->subject, $dispatcher->getSubject() );

		// The dipatchers forwards a registered state change but the Observer
		// is using the invoked source ($this) as means to communicate
		$dispatcher->setState( 'lila' );
		$this->assertEquals( 'lilaObserver was informed by source', $this->subject->ObserverSentAMessage );

	}

	/**
	 * @since 1.9
	 */
	public function lilaObserverCallback( $subject ) {
		return $subject->ObserverSentAMessage = 'lilaObserver was informed by source';
	}

	/**
	 * @since 1.9
	 */
	public function registerDispatcherCallback( ObservableDispatcher $dispatcher ) {

		$this->subject = $this->getMockBuilder( '\SMW\DispatchableSubject' )
			->setMethods( array( 'registerDispatcher' ) )
			->getMock();

		$dispatcher->setObservableSubject( $this->subject );
	}

}
