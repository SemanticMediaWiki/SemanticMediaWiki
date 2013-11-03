<?php

namespace SMW\Test;

use SMW\NullPropertyAnnotator;
use SMW\EmptyContext;

/**
 * @covers \SMW\NullPropertyAnnotator
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
class NullPropertyAnnotatorTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\NullPropertyAnnotator';
	}

	/**
	 * @since 1.9
	 *
	 * @return Observer
	 */
	private function newObserver() {

		return $this->newMockBuilder()->newObject( 'FakeObserver', array(
			'updateFoo' => array( $this, 'fooObserverCallback' )
		) );

	}

	/**
	 * @since 1.9
	 *
	 * @return NullPropertyAnnotator
	 */
	private function newInstance() {

		return new NullPropertyAnnotator(
			$this->newMockBuilder()->newObject( 'SemanticData' ),
			new EmptyContext()
		);

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
	public function testNotifyAndUpdateObserver() {

		$instance = $this->newInstance();
		$instance->attach( $this->newObserver() );

		$this->assertNull( $instance->getState() );
		$instance->setState( 'updateFoo' );

		$this->assertEquals( 'updateFoo', $instance->getState() );
		$this->assertEquals( 'fooObserverCallback', $instance->verifyCallback );

	}

	/**
	 * Verify that the Observer is reachable
	 *
	 * @since 1.9
	 */
	public function fooObserverCallback( $instance ) {

		$this->assertInstanceOf( '\SMW\SemanticData', $instance->getSemanticData() );
		$this->assertInstanceOf( '\SMW\ContextResource', $instance->withContext() );

		return $instance->verifyCallback = 'fooObserverCallback';
	}

}
