<?php

namespace SMW\Tests;

use SMW\IteratorFactory;

/**
 * @covers \SMW\IteratorFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class IteratorFactoryTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstructResultIterator() {
		$instance = new IteratorFactory();

		$result = $this->getMockBuilder( '\Wikimedia\Rdbms\ResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Iterators\ResultIterator',
			$instance->newResultIterator( $result )
		);
	}

	public function testCanConstructMappingIterator() {
		$instance = new IteratorFactory();

		$iterator = new \ArrayIterator( [] );

		$this->assertInstanceOf(
			'\SMW\Iterators\MappingIterator',
			$instance->newMappingIterator( $iterator, static function (){
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

		$this->expectException( 'SMW\Exception\FileNotFoundException' );

		$this->assertInstanceOf(
			'\SMW\Iterators\CsvFileIterator',
			$instance->newCsvFileIterator( 'Foo' )
		);
	}

}
