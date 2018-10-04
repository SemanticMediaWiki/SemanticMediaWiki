<?php

namespace SMW\Tests;

use SMW\IteratorFactory;

/**
 * @covers \SMW\IteratorFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class IteratorFactoryTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstructResultIterator() {

		$instance = new IteratorFactory();

		$result = $this->getMockBuilder( '\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Iterators\ResultIterator',
			$instance->newResultIterator( $result )
		);
	}

	public function testCanConstructMappingIterator() {

		$instance = new IteratorFactory();

		$iterator = $this->getMockBuilder( '\ArrayIterator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Iterators\MappingIterator',
			$instance->newMappingIterator( $iterator, function(){
			} )
		);
	}

	public function testCanConstructChunkedIterator() {

		$instance = new IteratorFactory();

		$iterator = $this->getMockBuilder( '\Iterator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Iterators\ChunkedIterator',
			$instance->newChunkedIterator( $iterator )
		);
	}

	public function testCanConstructAppendIterator() {

		$instance = new IteratorFactory();

		$this->assertInstanceOf(
			'\SMW\Iterators\AppendIterator',
			$instance->newAppendIterator()
		);
	}

	public function testCanConstructCsvFileIterator() {

		$instance = new IteratorFactory();

		$this->setExpectedException( 'SMW\Exception\FileNotFoundException' );

		$this->assertInstanceOf(
			'\SMW\Iterators\CsvFileIterator',
			$instance->newCsvFileIterator( 'Foo' )
		);
	}

}
