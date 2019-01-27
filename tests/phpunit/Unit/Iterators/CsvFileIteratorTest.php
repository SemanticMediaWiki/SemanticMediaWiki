<?php

namespace SMW\Tests\Iterators;

use SMW\Iterators\CsvFileIterator;
use SMW\Utils\TempFile;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Iterators\CsvFileIterator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CsvFileIteratorTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $file;
	private $tempFile;

	protected function setUp() {
		parent::setUp();

		$this->tempFile = new TempFile();
		$this->file = $this->tempFile->get( 'test.csv' );

		$this->tempFile->write( $this->file, 'Foo' );
	}

	protected function tearDown() {
		$this->tempFile->delete( $this->file );
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			CsvFileIterator::class,
			new CsvFileIterator( $this->file )
		);
	}

	public function testInvalidFileThrowsException() {

		$this->setExpectedException( '\SMW\Exception\FileNotFoundException' );
		new CsvFileIterator( 'Foo' );
	}

	public function testForEachOnCsvFileWithNoHeader() {

		$sample = [
			'1,Foo,abc',
			'2,Bar,123'
		];

		$this->tempFile->write( $this->file, implode( "\n", $sample ) );

		$instance = new CsvFileIterator(
			$this->file,
			false,
			','
		);

		$res = [];

		foreach ( $instance as $row ) {
			$res[] = $row;
		}

		$this->assertEmpty(
			$instance->getHeader()
		);

		$this->assertEquals(
			2,
			$instance->count()
		);

		$this->assertEquals(
			$res,
			[
				[ '1', 'Foo', 'abc' ],
				[ '2', 'Bar', '123' ]
			]
		);
	}

	public function testForEachOnCsvFileWithHeader() {

		$sample = [
			'No,Text,Other',
			'1,Foo,abc',
			'2,Bar,123'
		];

		$this->tempFile->write( $this->file, implode( "\n", $sample ) );

		$instance = new CsvFileIterator(
			$this->file,
			true,
			','
		);

		$res = [];

		foreach ( $instance as $row ) {
			$res[] = $row;
		}

		$this->assertEquals(
			$instance->getHeader(),
			[
				'No',
				'Text',
				'Other'
			]
		);

		$this->assertEquals(
			$res,
			[
				[ '1', 'Foo', 'abc' ],
				[ '2', 'Bar', '123' ]
			]
		);
	}

}
