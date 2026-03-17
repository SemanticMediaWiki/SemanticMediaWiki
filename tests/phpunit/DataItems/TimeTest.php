<?php

namespace SMW\Tests;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Time;
use SMW\Exception\DataItemException;
use SMW\MediaWiki\ExtendedDateTime;

/**
 * @covers \SMW\DataItems\Time
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class TimeTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			Time::class,
			new Time( Time::CM_GREGORIAN, 1970 )
		);
	}

	public function testNewFromTimestamp() {
		$instance = Time::newFromTimestamp( '1362200400' );

		$this->assertInstanceOf(
			Time::class,
			$instance
		);
	}

	public function testNewFromDateTime() {
		$instance = Time::newFromDateTime(
			new ExtendedDateTime( '2012-07-08 11:14:15.638276' )
		);

		$this->assertSame(
			15.638276,
			$instance->getSecond()
		);

		$instance = Time::newFromDateTime(
			new ExtendedDateTime( '1582-10-04' )
		);

		$this->assertEquals(
			Time::CM_JULIAN,
			$instance->getCalendarModel()
		);

		$instance = Time::newFromDateTime(
			new ExtendedDateTime( '1582-10-05' )
		);

		$this->assertEquals(
			Time::CM_GREGORIAN,
			$instance->getCalendarModel()
		);
	}

	public function testDateTimeRoundTrip() {
		$dateTime = new ExtendedDateTime( '2012-07-08 11:14:15.638276' );

		$instance = Time::newFromDateTime(
			$dateTime
		);

		$this->assertEquals(
			$dateTime,
			$instance->asDateTime()
		);
	}

	public function testDateTimeWithLargeMs() {
		$dateTime = new ExtendedDateTime( '1300-11-02 12:03:25.888499' );

		$instance = new Time(
			2, 1300, 11, 02, 12, 03, 25.888499
		);

		$this->assertEquals(
			$dateTime,
			$instance->asDateTime()
		);
	}

	public function testDateTimeWithHistoricDate() {
		$dateTime = new ExtendedDateTime( '-0900-02-02 00:00:00' );

		$instance = new Time(
			2, -900, 02, 02
		);

		$this->assertEquals(
			$dateTime,
			$instance->asDateTime()
		);
	}

	public function testDeserializationOnIncompleteFormat() {
		$instance = new Time(
			1, 2013, 0, 2, 0
		);

		$this->assertEquals(
			$instance,
			Time::doUnserialize( '1/2013/0/2/0/' )
		);
	}

	/**
	 * @dataProvider jdProvider
	 */
	public function testNewFromJD( $jd, $expected ) {
		$this->assertEquals(
			Time::doUnserialize( $expected ),
			Time::newFromJD( $jd )
		);
	}

	public function testTryToDeserializeOnNonNumericElementsThrowsException() {
		$this->expectException( DataItemException::class );
		Time::doUnserialize( '1/2013/0/2/0/foo' );
	}

	public function testTryToDeserializeOnInvalidCountOfElementsThrowsException() {
		$this->expectException( DataItemException::class );
		Time::doUnserialize( '1' );
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
