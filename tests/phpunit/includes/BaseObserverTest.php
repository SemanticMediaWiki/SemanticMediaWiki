<?php

namespace SMW\Test;

/**
 * @covers \SMW\BaseObserver
 * @covers \SMW\ObservableSubject
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
class ObserverTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return false;
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
	 * @return ObservableSubject
	 */
	private function newObservableSubject() {

		return $this->newMockBuilder()->newObject( 'FakeObservableSubject', array(
			'lulu' => array( $this, 'luluSubjectCallback' )
		) );

	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( '\SMW\Observer', $this->newObserver() );
		$this->assertInstanceOf( '\SMW\ObservableSubject', $this->newObservableSubject() );
	}

	/**
	 * @since 1.9
	 */
	public function testInvokeAndDetach() {

		$subject  = $this->getMockForAbstractClass( '\SMW\ObservableSubject' );

		// Same Observer instance attached twice results in only one registered object
		$observer = $this->getMockForAbstractClass( '\SMW\BaseObserver', array( $subject ) );
		$subject->attach( $observer );

		$this->assertCount( 1, $subject->getObservers() );
		$subject->detach( $observer );
		$this->assertCount( 0, $subject->getObservers() );

		// Two different instances of an Observer
		$this->getMockForAbstractClass( '\SMW\BaseObserver', array( $subject ) );
		$observer = $this->getMockForAbstractClass( '\SMW\BaseObserver', array( $subject ) );

		$this->assertCount( 2, $subject->getObservers() );
		$subject->detach( $observer );
		$this->assertCount( 1, $subject->getObservers() );

	}

	/**
	 * @since 1.9
	 */
	public function testNotifyAndUpdate() {

		$subject = $this->newObservableSubject();
		$subject->attach( $this->newObserver() );

		$this->assertNull( $subject->getState() );
		$subject->lulu( $subject );

		$this->assertCount( 1, $subject->getObservers() );
		$this->assertEquals( 'lila', $subject->getState() );
		$this->assertEquals( 'lila was informed by lulu', $subject->info );

	}

	/**
	 * Notify the observer to execute "lila" (which is part of the Observer)
	 *
	 * @since 1.9
	 */
	public function luluSubjectCallback( $subject ) {
		$subject->setState( 'lila' );
	}

	/**
	 * Verify that the Observer was acting on the invoked Subject
	 *
	 * @since 1.9
	 */
	public function lilaObserverCallback( $subject ) {
		return $subject->info = 'lila was informed by lulu';
	}

}
