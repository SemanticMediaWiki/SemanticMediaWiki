<?php

namespace SMW\Tests\Maintenance\Jobs;

use Exception;
use PHPUnit\Framework\TestCase;
use SMW\Maintenance\ExceptionFileLogger;
use SMW\Options;
use SMW\Utils\File;

/**
 * @covers \SMW\Maintenance\ExceptionFileLogger
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class ExceptionFileLoggerTest extends TestCase {

	private $file;

	protected function setUp(): void {
		parent::setUp();

		$this->file = $this->getMockBuilder( File::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ExceptionFileLogger::class,
			new ExceptionFileLogger()
		);
	}

	public function testGetter() {
		$instance = new ExceptionFileLogger();

		$instance->setOptions( new Options( [
			'exception-log' => __DIR__
		] ) );

		$this->assertIsBool(

			$instance->getExceptionFile()
		);

		$this->assertIsInt(

			$instance->getExceptionCount()
		);
	}

	public function testDoWriteExceptionLog() {
		$this->file->expects( $this->once() )
			->method( 'write' );

		$instance = new ExceptionFileLogger( 'foo', $this->file );

		$instance->recordException(
			'Foo',
			new Exception( 'Bar' )
		);

		$this->assertSame(
			1,
			$instance->getExceptionCount()
		);

		$instance->doWrite();
	}

}
