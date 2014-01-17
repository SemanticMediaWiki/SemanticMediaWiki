<?php

namespace SMW\Tests\Log;

use SMW\Log\LoggerInterface;
use SMW\Log\LoggerAware;
use SMW\Log\NullLogger;

/**
 * @covers \SMW\Log\LoggerAware
 * @covers \SMW\Log\BaseLogger
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9.0.3
 *
 * @author mwjames
 */
class FauxLoggerAwareTest extends \PHPUnit_Framework_TestCase {

	public function testCanLog() {

		$logger = $this->getMockForAbstractClass( '\SMW\Log\BaseLogger' );

		$logger->expects( $this->once() )
			->method( 'log' )
			->with( $this->logicalOr(
				$this->equalTo( null ),
				$this->equalTo( 'Foo' )
			) )
			->will( $this->returnValue( 'Foo' ) );

		$loggerAware = new FauxLoggerAware;

		$this->assertInstanceOf( 'SMW\Log\NullLogger', $loggerAware->getLogger() );
		$this->assertEquals( $logger, $loggerAware->setLogger( $logger )->getLogger() );
		$this->assertEquals( 'Foo', $loggerAware->getLogger()->debug( 'Foo' ) );

	}

}

class FauxLoggerAware implements LoggerAware {

	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
		return $this;
	}

	public function getLogger() {

		if ( !isset( $this->logger ) ) {
			$this->logger = new NullLogger;
		};

		return $this->logger;
	}

}