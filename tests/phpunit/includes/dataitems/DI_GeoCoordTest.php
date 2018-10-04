<?php

namespace SMW\Tests;

use SMW\Exception\DataItemException;

/**
 * @covers SMWDIGeoCoord
 * @covers SMWDataItem
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWDIGeoCoordTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testConstructorWithArrayArgumentForm() {
		$coordinate = new \SMWDIGeoCoord( [ 'lat' => 13.37, 'lon' => 42.42 ] );

		$this->assertSame( 13.37, $coordinate->getLatitude() );
		$this->assertSame( 42.42, $coordinate->getLongitude() );
	}

	public function testConstructorWithMultipleArgumentsForm() {
		$coordinate = new \SMWDIGeoCoord( 13.37, 42.42 );

		$this->assertSame( 13.37, $coordinate->getLatitude() );
		$this->assertSame( 42.42, $coordinate->getLongitude() );
	}

	public function testWhenConstructingWithIntegers_gettersReturnFloats() {
		$coordinate = new \SMWDIGeoCoord( 13, 42 );

		$this->assertSame( 13.0, $coordinate->getLatitude() );
		$this->assertSame( 42.0, $coordinate->getLongitude() );
	}

	public function testWhenOnlyProvidingLatitudeArgument_constructorThrowsException() {
		$this->setExpectedException( DataItemException::class );
		new \SMWDIGeoCoord( 13 );
	}

	public function testWhenProvidingNonNumericalArgument_constructorThrowsException() {
		$this->setExpectedException( DataItemException::class );
		new \SMWDIGeoCoord( 13, null );
	}

	public function testWhenProvidingArrayWithNonNumericalArgument_constructorThrowsException() {
		$this->setExpectedException( DataItemException::class );
		new \SMWDIGeoCoord( [ 'lat' => null, 'lon' => 42.42 ] );
	}

	public function testObjectEqualsItself() {
		$coordinate = new \SMWDIGeoCoord( 13, 42 );
		$this->assertTrue( $coordinate->equals( $coordinate ) );
	}

	public function testObjectEqualsDifferentInstancesWithEqualValues() {
		$coordinate = new \SMWDIGeoCoord( 13, 42 );
		$this->assertTrue( $coordinate->equals( new \SMWDIGeoCoord( 13.0, 42.0 ) ) );
	}

	public function testObjectDoesNotEqualInstancesWithDifferentValues() {
		$coordinate = new \SMWDIGeoCoord( 13, 42 );
		$this->assertFalse( $coordinate->equals( new \SMWDIGeoCoord( 1, 2 ) ) );
	}

}
