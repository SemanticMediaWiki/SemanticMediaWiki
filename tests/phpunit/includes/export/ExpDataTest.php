<?php

namespace SMW\Tests\Exporter;

use SMW\Exporter\Element\ExpLiteral;
use SMW\Exporter\Element\ExpNsResource;
use SMWExpData as ExpData;

/**
 * @covers \SMWExpData
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ExpDataTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstructor() {

		$expNsResource = $this->getMockBuilder( '\SMW\Exporter\Element\ExpNsResource' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMWExpData',
			new ExpData( $expNsResource )
		);
	}

	/**
	 * @dataProvider expDataHashProvider
	 */
	public function testGetHash( $expData, $expected ) {

		$this->assertEquals(
			$expected,
			$expData->getHash()
		);
	}

	public function expDataHashProvider() {

		#0
		$expData = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$provider[] = array(
			$expData,
			'4dc04c87e9660854a5609ff132175fd5'
		);

		#1
		$expData = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$provider[] = array(
			$expData,
			'5702e5e8c6145aaf8d89840a4a3b18c2'
		);

		#2
		$expData = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Bar', 'Foo' )
		);

		$provider[] = array(
			$expData,
			'13edcedd007979f5638fbc958f0cdaf8'
		);

		#3 Same as 2 but different sorting/same hash
		$expData = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Bar', 'Foo' )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$provider[] = array(
			$expData,
			'13edcedd007979f5638fbc958f0cdaf8'
		);

		#4 Nesting
		$expDataLevel2 = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$expDataLevel2->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$expData = new ExpData(
			new ExpNsResource( 'Foo', 'Bar', 'Mo', null )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			new ExpLiteral( 'Foo', 'Bar' )
		);

		$expData->addPropertyObjectValue(
			new ExpNsResource( 'Li', 'La', 'Lu', null ),
			$expDataLevel2
		);

		$provider[] = array(
			$expData,
			'e684e7640a201d2d33e035aaa866c1ac'
		);

		return $provider;
	}

}
