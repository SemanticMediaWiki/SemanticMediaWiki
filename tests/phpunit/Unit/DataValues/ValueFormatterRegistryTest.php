<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ValueFormatterRegistry;

/**
 * @covers \SMW\DataValues\ValueFormatterRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ValueFormatterRegistryTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		ValueFormatterRegistry::getInstance()->clear();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$dispatchingDescriptionDeserializer = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DispatchingDataValueFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatterRegistry',
			new ValueFormatterRegistry( $dispatchingDescriptionDeserializer )
		);

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatterRegistry',
			ValueFormatterRegistry::getInstance()
		);
	}

	public function testCanConstructOnDefaultNoValueFormatter() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ValueFormatterRegistry();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\NoValueFormatter',
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testCanConstructMonolingualTextValue() {

		$dataValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ValueFormatterRegistry();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter',
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

	public function testRegisterAdditionalDataValueFormatter() {

		$dataValueFormatter = $this->getMockBuilder( '\SMW\DataValues\ValueFormatters\DataValueFormatter' )
			->disableOriginalConstructor()
			->setMethods( array( 'isFormatterFor' ) )
			->getMockForAbstractClass();

		$dataValueFormatter->expects( $this->once() )
			->method( 'isFormatterFor' )
			->will( $this->returnValue( true ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new ValueFormatterRegistry();
		$instance->registerDataValueFormatter( $dataValueFormatter );

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueFormatters\DataValueFormatter',
			$instance->getDataValueFormatterFor( $dataValue )
		);
	}

}
