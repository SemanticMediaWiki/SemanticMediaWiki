<?php

namespace SMW\Tests\Maintenance\Jobs;

use SMW\Maintenance\ExceptionFileLogger;
use SMW\Options;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Maintenance\ExceptionFileLogger
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ExceptionFileLoggerTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $file;

	protected function setUp(): void {
		parent::setUp();

		$this->file = $this->getMockBuilder( '\SMW\Utils\File' )
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
			new \Exception( 'Bar' )
		);

		$this->assertSame(
			1,
			$instance->getExceptionCount()
		);

		$instance->doWrite();
	}

}
