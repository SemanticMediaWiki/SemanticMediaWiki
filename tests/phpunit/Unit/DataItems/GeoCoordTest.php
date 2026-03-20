<?php

namespace SMW\Tests\Unit\DataItems;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\GeoCoord;
use SMW\Exception\DataItemException;

/**
 * @covers \SMW\DataItems\GeoCoord
 * @covers \SMW\DataItems\DataItem
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class GeoCoordTest extends TestCase {

	public function testConstructorWithArrayArgumentForm() {
		$coordinate = new GeoCoord( [ 'lat' => 13.37, 'lon' => 42.42 ] );

		$this->assertSame( 13.37, $coordinate->getLatitude() );
		$this->assertSame( 42.42, $coordinate->getLongitude() );
	}

	public function testConstructorWithMultipleArgumentsForm() {
		$coordinate = new GeoCoord( 13.37, 42.42 );

		$this->assertSame( 13.37, $coordinate->getLatitude() );
		$this->assertSame( 42.42, $coordinate->getLongitude() );
	}

	public function testWhenConstructingWithIntegers_gettersReturnFloats() {
		$coordinate = new GeoCoord( 13, 42 );

		$this->assertSame( 13.0, $coordinate->getLatitude() );
		$this->assertSame( 42.0, $coordinate->getLongitude() );
	}

	public function testWhenOnlyProvidingLatitudeArgument_constructorThrowsException() {
		$this->expectException( DataItemException::class );
		new GeoCoord( 13 );
	}

	public function testWhenProvidingNonNumericalArgument_constructorThrowsException() {
		$this->expectException( DataItemException::class );
		new GeoCoord( 13, null );
	}

	public function testWhenProvidingArrayWithNonNumericalArgument_constructorThrowsException() {
		$this->expectException( DataItemException::class );
		new GeoCoord( [ 'lat' => null, 'lon' => 42.42 ] );
	}

	public function testObjectEqualsItself() {
		$coordinate = new GeoCoord( 13, 42 );
		$this->assertTrue( $coordinate->equals( $coordinate ) );
	}

	public function testObjectEqualsDifferentInstancesWithEqualValues() {
		$coordinate = new GeoCoord( 13, 42 );
		$this->assertTrue( $coordinate->equals( new GeoCoord( 13.0, 42.0 ) ) );
	}

	public function testObjectDoesNotEqualInstancesWithDifferentValues() {
		$coordinate = new GeoCoord( 13, 42 );
		$this->assertFalse( $coordinate->equals( new GeoCoord( 1, 2 ) ) );
	}

	public function testNewFromLatLong() {
		$coordinate = GeoCoord::newFromLatLong( 13.0, 42.0 );
		$this->assertSame( 13.0, $coordinate->getLatitude() );
		$this->assertSame( 42.0, $coordinate->getLongitude() );
	}

}
