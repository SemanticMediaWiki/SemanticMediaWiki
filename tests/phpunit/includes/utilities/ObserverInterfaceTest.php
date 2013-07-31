<?php

namespace SMW\Test;

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
 * @covers \SMW\Subject
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ObserverInterfaceTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return false;
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
	 * Helper method that returns a Subject object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return Subject
	 */
	private function newObserverSubject() {

		$subject = $this->getMockBuilder( '\SMW\Subject' )
			->setMethods( array( 'lulu' ) )
			->getMock();

		$subject->expects( $this->any() )
			->method( 'lulu' )
			->will( $this->returnCallback( array( $this, 'luluSubjectCallback' ) ) );

		return $subject;

	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( '\SMW\Observer', $this->newObserver() );
		$this->assertInstanceOf( '\SMW\Subject', $this->newObserverSubject() );
	}

	/**
	 * @since 1.9
	 */
	public function testInvokeAndDetach() {

		$subject  = $this->getMockForAbstractClass( '\SMW\Subject' );

		// Same Observer instance attached twice results in only one registered object
		$observer = $this->getMockForAbstractClass( '\SMW\Observer', array( $subject ) );
		$subject->attach( $observer );

		$this->assertCount( 1, $subject->getObservers() );
		$subject->detach( $observer );
		$this->assertCount( 0, $subject->getObservers() );

		// Two different instances of an Observer
		$this->getMockForAbstractClass( '\SMW\Observer', array( $subject ) );
		$observer = $this->getMockForAbstractClass( '\SMW\Observer', array( $subject ) );

		$this->assertCount( 2, $subject->getObservers() );
		$subject->detach( $observer );
		$this->assertCount( 1, $subject->getObservers() );

	}

	/**
	 * @since 1.9
	 */
	public function testNotifyAndUpdate() {

		$subject = $this->newObserverSubject();
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
