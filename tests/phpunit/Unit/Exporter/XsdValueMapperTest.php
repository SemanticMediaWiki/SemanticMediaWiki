<?php

namespace SMW\Tests\Unit\Exporter;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Blob;
use SMW\DataItems\Boolean;
use SMW\DataItems\Concept;
use SMW\DataItems\Container;
use SMW\DataItems\GeoCoord;
use SMW\DataItems\Number;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\Uri;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\Exporter\XsdValueMapper;

/**
 * @covers \SMW\Exporter\XsdValueMapper
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class XsdValueMapperTest extends TestCase {

	/**
	 * @dataProvider supportedDataItemProvider
	 */
	public function testMatchSupportedTypes( $dataItem, $xsdValue, $xsdType ) {
		[ $type, $value ] = XsdValueMapper::map( $dataItem );

		$this->assertEquals(
			$xsdValue,
			$value
		);

		$this->assertStringContainsString(
			$xsdType,
			$type
		);
	}

	/**
	 * @dataProvider unsupportedDataItemProvider
	 */
	public function testTryToMatchUnsupportedTypeThrowsException( $dataItem ) {
		$this->expectException( 'RuntimeException' );
		XsdValueMapper::map( $dataItem );
	}

	public function supportedDataItemProvider() {
		# 0
		$provider[] = [
			new Number( 42 ),
			'42',
			'double'
		];

		# 1
		$provider[] = [
			new Blob( 'Test' ),
			'Test',
			'string'
		];

		# 2
		$provider[] = [
			new Boolean( true ),
			'true',
			'boolean'
		];

		# 3
		$provider[] = [
			new Time( 1, '1970' ),
			'1970',
			'gYear'
		];

		# 4
		$provider[] = [
			new Time( 1, '1970', '12' ),
			'1970-12',
			'gYearMonth'
		];

		# 5
		$provider[] = [
			new Time( 1, '1970', '12', '31' ),
			'1970-12-31Z',
			'date'
		];

		# 6
		$provider[] = [
			new Time( 1, '1970', '12', '31', '12' ),
			'1970-12-31T12:00:00Z',
			'dateTime'
		];

		return $provider;
	}

	public function unsupportedDataItemProvider() {
		$dataItem = $this->getMockBuilder( '\SMWDataItem' )
			->disableOriginalConstructor()
			->setMethods( [ '__toString' ] )
			->getMockForAbstractClass();

		$dataItem->expects( $this->any() )
			->method( '__toString' )
			->willReturn( 'Foo' );

		# 0
		$provider[] = [
			$dataItem
		];

		# 1
		$provider[] = [
			new GeoCoord( [ 'lat' => 52, 'lon' => 1 ] )
		];

		# 2
		$provider[] = [
			new Concept( 'Foo', '', '', '', '' )
		];

		# 3
		$provider[] = [
			new Uri( 'http', '//example.org', '', '' )
		];

		# 4
		$provider[] = [
			new Container( new ContainerSemanticData( new WikiPage( 'Foo', NS_MAIN ) ) )
		];

		# 5
		$provider[] = [
			new WikiPage( 'Foo', NS_MAIN )
		];

		# 6
		$provider[] = [
			new Property( 'Foo' )
		];

		# 7 Not a gregorian calendar model
		$provider[] = [
			new Time( 2, '1970' )
		];

		return $provider;
	}

}
