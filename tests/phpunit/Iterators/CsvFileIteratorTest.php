<?php

namespace SMW\Tests\Iterators;

use SMW\Iterators\CsvFileIterator;
use SMW\Tests\PHPUnitCompat;
use SMW\Utils\TempFile;

/**
 * @covers \SMW\Iterators\CsvFileIterator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class CsvFileIteratorTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $file;
	private $tempFile;

	protected function setUp(): void {
		parent::setUp();

		$this->tempFile = new TempFile();
		$this->file = $this->tempFile->get( 'test.csv' );

		$this->tempFile->write( $this->file, 'Foo' );
	}

	protected function tearDown(): void {
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
		$this->expectException( '\SMW\Exception\FileNotFoundException' );
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
			[
				[ '1', 'Foo', 'abc' ],
				[ '2', 'Bar', '123' ]
			],
			$res
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
			[
				'No',
				'Text',
				'Other'
			],
			$instance->getHeader()
		);

		$this->assertEquals(
			[
				[ '1', 'Foo', 'abc' ],
				[ '2', 'Bar', '123' ]
			],
			$res
		);
	}

}
