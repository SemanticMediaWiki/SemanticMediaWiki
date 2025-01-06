<?php

namespace SMW\Tests\Utils\Mock;

use RuntimeException;

/**
 * Convenience mock builder for Iterator classes
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class IteratorMockBuilder extends \PHPUnit\Framework\TestCase {

	private $iteratorClass;
	private $items = [];
	private $methods = [];
	private $counter = 0;

	/**
	 * @since  2.0
	 *
	 * @param string $iteratorClass
	 *
	 * @return IteratorMockBuilder
	 */
	public function setClass( $iteratorClass ) {
		$this->iteratorClass = $iteratorClass;
		return $this;
	}

	/**
	 * @since  2.0
	 *
	 * @param array $items
	 *
	 * @return IteratorMockBuilder
	 */
	public function with( array $items ) {
		$this->items = $items;
		return $this;
	}

	/**
	 * @since  2.5
	 *
	 * @param array $methods
	 *
	 * @return IteratorMockBuilder
	 */
	public function setMethods( array $methods ) {
		$this->methods = $methods;
		return $this;
	}

	/**
	 * @note When other methods called before the actual current/next then
	 * set the counter to ensure the starting point matches the expected
	 * InvokeCount.
	 *
	 * @since  2.5
	 *
	 * @param int $num
	 *
	 * @return IteratorMockBuilder
	 */
	public function incrementInvokedCounterBy( $num ) {
		$this->counter += $num;
		return $this;
	}

	/**
	 * @since  2.0
	 *
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 * @throws RuntimeException
	 */
	public function getMockForIterator() {
		$instance = $this->getMockBuilder( $this->iteratorClass )
			->disableOriginalConstructor()
			->setMethods( $this->methods )
			->getMock();

		if ( !$instance instanceof \Iterator ) {
			throw new RuntimeException( "Instance is not an Iterator" );
		}

		$instance->expects( $this->at( $this->counter++ ) )
			->method( 'rewind' );

		foreach ( $this->items as $key => $value ) {

			$instance->expects( $this->at( $this->counter++ ) )
				->method( 'valid' )
				->willReturn( true );

			$instance->expects( $this->at( $this->counter++ ) )
				->method( 'current' )
				->willReturn( $value );

			$instance->expects( $this->at( $this->counter++ ) )
				->method( 'next' );
		}

		$instance->expects( $this->at( $this->counter++ ) )
			->method( 'valid' )
			->willReturn( false );

		return $instance;
	}

	/**
	 * @since  2.0
	 *
	 * @return int
	 */
	public function getLastCounter() {
		return $this->counter;
	}
}
