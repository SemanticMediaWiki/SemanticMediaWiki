<?php

namespace SMW\Tests;

use PHPUnit\Framework\TestCase;
use SMW\Exception\FileNotFoundException;
use SMW\IteratorFactory;
use SMW\Iterators\AppendIterator;
use SMW\Iterators\ChunkedIterator;
use SMW\Iterators\CsvFileIterator;
use SMW\Iterators\MappingIterator;
use SMW\Iterators\ResultIterator;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @covers \SMW\IteratorFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class IteratorFactoryTest extends TestCase {

	public function testCanConstructResultIterator() {
		$instance = new IteratorFactory();

		$result = $this->getMockBuilder( ResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newResultIterator( $result )
		);
	}

	public function testCanConstructMappingIterator() {
		$instance = new IteratorFactory();

		$iterator = new \ArrayIterator( [] );

		$this->assertInstanceOf(
			MappingIterator::class,
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
			ChunkedIterator::class,
			$instance->newChunkedIterator( $iterator )
		);
	}

	public function testCanConstructAppendIterator() {
		$instance = new IteratorFactory();

		$this->assertInstanceOf(
			AppendIterator::class,
			$instance->newAppendIterator()
		);
	}

	public function testCanConstructCsvFileIterator() {
		$instance = new IteratorFactory();

		$this->expectException( FileNotFoundException::class );

		$this->assertInstanceOf(
			CsvFileIterator::class,
			$instance->newCsvFileIterator( 'Foo' )
		);
	}

}
