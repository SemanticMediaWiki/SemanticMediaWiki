<?php

namespace SMW\Tests\MediaWiki;

use DateTime;
use SMW\MediaWiki\LocalTime;

/**
 * @covers \SMW\MediaWiki\LocalTime
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class LocalTimeTest extends \PHPUnit_Framework_TestCase {

	public function testNoModifiedLocalTime() {

		$dateTime = LocalTime::getLocalizedTime(
			new DateTime( '2017-08-01 10:00:00+00:00' )
		);

		$this->assertFalse(
			$dateTime->hasLocalTimeCorrection
		);
	}

	public function testModifiedTimeWithPositiveLocalTimeOffset() {

		$dti = new DateTime( '2017-08-01 10:00:00+00:00' );

		LocalTime::setLocalTimeOffset( 60 );
		$dateTime = LocalTime::getLocalizedTime( $dti );

		$this->assertTrue(
			$dateTime->hasLocalTimeCorrection
		);

		$this->assertEquals(
			'2017-08-01 11:00:00',
			$dateTime->format( 'Y-m-d H:i:s' )
		);
	}

	public function testModifiedTimeWithNegativeLocalTimeOffset() {

		$dti = new DateTime( '2017-08-01 10:00:00+00:00' );

		LocalTime::setLocalTimeOffset( -60 );
		$dateTime = LocalTime::getLocalizedTime( $dti );

		$this->assertTrue(
			$dateTime->hasLocalTimeCorrection
		);

		$this->assertEquals(
			'2017-08-01 09:00:00',
			$dateTime->format( 'Y-m-d H:i:s' )
		);
	}

	public function testModifiedTimeWithUserTimeCorrection() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'getOption' )
			->with( $this->equalTo( 'timecorrection' ) )
			->will( $this->returnValue( 'ZoneInfo|+120|Europe/Berlin' ) );

		$dti = new DateTime( '2017-08-01 10:00:00+00:00' );

		LocalTime::setLocalTimeOffset( 0 );
		$dateTime = LocalTime::getLocalizedTime( $dti, $user );

		$this->assertTrue(
			$dateTime->hasLocalTimeCorrection
		);

		$this->assertEquals(
			'2017-08-01 12:00:00',
			$dateTime->format( 'Y-m-d H:i:s' )
		);
	}

	public function testModifiedTimeWithUserTimeCorrectionOnInvalidZone() {

		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();

		$user->expects( $this->once() )
			->method( 'getOption' )
			->with( $this->equalTo( 'timecorrection' ) )
			->will( $this->returnValue( 'ZoneInfo|+125|Foo' ) );

		$dti = new DateTime( '2017-08-01 10:00:00+00:00' );

		$dateTime = LocalTime::getLocalizedTime( $dti, $user );

		$this->assertTrue(
			$dateTime->hasLocalTimeCorrection
		);

		$this->assertEquals(
			'2017-08-01 12:05:00',
			$dateTime->format( 'Y-m-d H:i:s' )
		);
	}

}
