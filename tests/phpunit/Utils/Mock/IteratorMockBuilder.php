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

		$items = array_values( $this->items );
		$position = 0;

		$instance->expects( $this->any() )
			->method( 'rewind' )
			->willReturnCallback( static function () use ( &$position ) {
				$position = 0;
			} );

		$instance->expects( $this->any() )
			->method( 'valid' )
			->willReturnCallback( static function () use ( &$position, $items ) {
				return $position < count( $items );
			} );

		$instance->expects( $this->any() )
			->method( 'current' )
			->willReturnCallback( static function () use ( &$position, $items ) {
				return $items[$position] ?? null;
			} );

		$instance->expects( $this->any() )
			->method( 'next' )
			->willReturnCallback( static function () use ( &$position ) {
				$position++;
			} );

		$this->counter += 2 + ( count( $items ) * 3 );

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
