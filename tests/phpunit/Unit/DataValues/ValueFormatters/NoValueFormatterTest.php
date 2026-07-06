<?php

namespace SMW\Tests\Unit\DataValues\ValueFormatters;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\DataItem;
use SMW\DataValues\DataValue;
use SMW\DataValues\ValueFormatters\NoValueFormatter;

/**
 * @covers \SMW\DataValues\ValueFormatters\NoValueFormatter
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class NoValueFormatterTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			NoValueFormatter::class,
			new NoValueFormatter()
		);
	}

	public function testIsFormatterForValidation() {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new NoValueFormatter();

		$this->assertTrue(
			$instance->isFormatterFor( $dataValue )
		);
	}

	public function testFormat() {
		$dataItem = $this->getMockBuilder( DataItem::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getSerialization' ] )
			->getMockForAbstractClass();

		$dataItem->expects( $this->once() )
			->method( 'getSerialization' )
			->willReturn( 'isFromSerializationMethod' );

		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getDataItem' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->any() )
			->method( 'isValid' )
			->willReturn( true );

		$dataValue->expects( $this->once() )
			->method( 'getDataItem' )
			->willReturn( $dataItem );

		$instance = new NoValueFormatter( $dataValue );

		$this->assertEquals(
			'isFromSerializationMethod',
			$instance->format( NoValueFormatter::VALUE )
		);
	}

	public function testTryToFormatOnMissingDataValueThrowsException() {
		$instance = new NoValueFormatter();

		$this->expectException( 'RuntimeException' );
		$instance->format( NoValueFormatter::VALUE );
	}

}
