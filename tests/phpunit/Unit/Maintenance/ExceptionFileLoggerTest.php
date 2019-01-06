<?php

namespace SMW\Tests\Maintenance\Jobs;

use SMW\Maintenance\ExceptionFileLogger;
use SMW\Options;

/**
 * @covers \SMW\Maintenance\ExceptionFileLogger
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ExceptionFileLoggerTest extends \PHPUnit_Framework_TestCase {

	private $file;

	protected function setUp() {
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

		$this->assertInternalType(
			'boolean',
			$instance->getExceptionFile()
		);

		$this->assertInternalType(
			'integer',
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

		$this->assertEquals(
			1,
			$instance->getExceptionCount()
		);

		$instance->doWrite();
	}

}
