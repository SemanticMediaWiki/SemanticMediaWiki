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

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Maintenance\ExceptionFileLogger',
			new ExceptionFileLogger()
		);
	}

	public function testGetter() {

		$instance = new ExceptionFileLogger();

		$instance->setOptions( new Options( array(
			'exception-log' => __DIR__
		) ) );

		$this->assertInternalType(
			'boolean',
			$instance->getExceptionFile()
		);

		$this->assertInternalType(
			'integer',
			$instance->getExceptionCounter()
		);
	}

	public function testDoWriteExceptionLog() {

		$instance = new ExceptionFileLogger();

		$instance->setOptions( new Options( array(
			'exception-log' => __DIR__
		) ) );

		$instance->doWriteExceptionLog(
			array( 'Foo' )
		);

		$this->assertEquals(
			0,
			$instance->getExceptionCounter()
		);
	}

}
