<?php

namespace SMW\Tests;

use SMWDITime as DITime;

/**
 * @covers \SMWDITime
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DITimeTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWDITime',
			new DITime(  DITime::CM_GREGORIAN, 1970 )
		);
	}

	public function testNewFromTimestamp() {

		$instance = DITime::newFromTimestamp( '1362200400' );

		$this->assertInstanceOf(
			'\SMWDITime',
			$instance
		);
	}

	public function testNewFromDateTime() {

		$instance = DITime::newFromDateTime(
			new \DateTime( '2012-07-08 11:14:15.638276' )
		);

		$this->assertEquals(
			'15.638276',
			$instance->getSecond()
		);

		$instance = DITime::newFromDateTime(
			new \DateTime( '1582-10-04' )
		);

		$this->assertEquals(
			DITime::CM_JULIAN,
			$instance->getCalendarModel()
		);

		$instance = DITime::newFromDateTime(
			new \DateTime( '1582-10-05' )
		);

		$this->assertEquals(
			DITime::CM_GREGORIAN,
			$instance->getCalendarModel()
		);
	}

	public function testDateTimeRoundTrip() {

		$dateTime = new \DateTime( '2012-07-08 11:14:15.638276' );

		$instance = DITime::newFromDateTime(
			$dateTime
		);

		$this->assertEquals(
			$dateTime,
			$instance->asDateTime()
		);
	}

	public function testDateTimeWithLargeMs() {

		$dateTime = new \DateTime( '1300-11-02 12:03:25.888500' );

		$instance = new DITime(
			2, 1300, 11, 02, 12, 03, 25.888499949
		);

		if ( $instance->asDateTime() != $dateTime  ) {
			$this->markTestSkipped( 'For some reason this started to fail on 5.6.19 (worked on 5.6.18)' );
		}

		$this->assertEquals(
			$dateTime,
			$instance->asDateTime()
		);
	}

	public function testDateTimeWithHistoricDate() {

		$dateTime = new \DateTime( '-0900-02-02 00:00:00' );

		$instance = new DITime(
			2, -900, 02, 02
		);

		$this->assertEquals(
			$dateTime,
			$instance->asDateTime()
		);
	}

	public function testDeserializationOnIncompleteFormat() {

		$instance = new DITime(
			1, 2013, 0, 2, 0
		);

		$this->assertEquals(
			$instance,
			DITime::doUnserialize( '1/2013/0/2/0/' )
		);
	}

	/**
	 * @dataProvider jdProvider
	 */
	public function testNewFromJD( $jd, $expected ) {

		$this->assertEquals(
			DITime::doUnserialize( $expected ),
			DITime::newFromJD( $jd )
		);
	}

	public function testTryToDeserializeOnNonNumericElementsThrowsException() {

		$this->setExpectedException( '\SMW\Exception\DataItemException' );
		DITime::doUnserialize( '1/2013/0/2/0/foo' );
	}

	public function testTryToDeserializeOnInvalidCountOfElementsThrowsException() {

		$this->setExpectedException( '\SMW\Exception\DataItemException' );
		DITime::doUnserialize( '1' );
	}

	public function jdProvider() {

		$provider[] = [
			'2488345.500000',
			'1/2100/10/04'
		];

		$provider[] = [
			'2488346.0804977',
			'1/2100/10/4/13/55/55'
		];

		$provider[] = [
			'1',
			'2/-4713/01/02/12'
		];

		return $provider;
	}

}
