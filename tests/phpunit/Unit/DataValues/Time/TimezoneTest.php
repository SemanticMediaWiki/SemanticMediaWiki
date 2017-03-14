<?php

namespace SMW\Tests\DataValues\Time;

use SMW\DataItemFactory;
use SMW\DataValues\Time\Timezone;

/**
 * @covers \SMW\DataValues\Time\Timezone
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TimezoneTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Timezone::class,
			new Timezone()
		);
	}

	public function testListShortAbbreviations() {

		$this->assertInternalType(
			'array',
			Timezone::listShortAbbreviations()
		);
	}

	/**
	 * @dataProvider timezoneProvider
	 */
	public function testIsValidAndIsMilitary( $abbrevation, $isValid, $isMilitary ) {

		$this->assertEquals(
			$isValid,
			Timezone::isValid( $abbrevation )
		);

		$this->assertEquals(
			$isMilitary,
			Timezone::isMilitary( $abbrevation )
		);
	}

	/**
	 * @dataProvider timezoneProvider
	 */
	public function testGetIdByAbbreviation( $abbrevation, $isValid, $isMilitary, $expectedId ) {

		$this->assertEquals(
			$expectedId,
			Timezone::getIdByAbbreviation( $abbrevation )
		);
	}

	/**
	 * @dataProvider offsetProvider
	 */
	public function testGetOffsetByAbbreviation( $abbrevation, $expected ) {

		$this->assertEquals(
			$expected,
			Timezone::getOffsetByAbbreviation( $abbrevation )
		);
	}

	public function timezoneProvider() {

		$provider[] = [
			'UTC',
			true,
			false,
			0
		];

		$provider[] = [
			'Z',
			true,
			true,
			1
		];

		$provider[] = [
			'Unknown',
			false,
			false,
			false
		];

		$provider[] = [
			'Asia/Tokyo',
			true,
			false,
			'Asia/Tokyo'
		];

		$provider[] = [
			'America/Los Angeles',
			true,
			false,
			'America/Los_Angeles'
		];

		$provider[] = [
			'America/Los_Angeles',
			true,
			false,
			'America/Los_Angeles'
		];

		return $provider;
	}

	public function offsetProvider() {

		$provider[] = [
			'UTC',
			0
		];

		$provider[] = [
			'Z',
			0
		];

		$provider[] = [
			'Unknown',
			false
		];

		$provider[] = [
			'Asia/Tokyo',
			32400
		];

		// Maybe return PST or PDT during Daylight Savings Time
		/*
		$provider[] = array(
			'America/Los Angeles',
			-25200
		);

		$provider[] = array(
			'America/Los_Angeles',
			-25200
		);
		*/
		return $provider;
	}

}
