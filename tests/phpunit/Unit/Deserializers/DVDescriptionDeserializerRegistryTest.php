<?php

namespace SMW\Tests\Deserializers;

use SMW\Deserializers\DVDescriptionDeserializerRegistry;

/**
 * @covers \SMW\Deserializers\DVDescriptionDeserializerRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class DVDescriptionDeserializerRegistryTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		DVDescriptionDeserializerRegistry::getInstance()->clear();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$dispatchingDescriptionDeserializer = $this->getMockBuilder( '\SMW\Deserializers\DVDescriptionDeserializer\DispatchingDescriptionDeserializer' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializerRegistry',
			new DVDescriptionDeserializerRegistry( $dispatchingDescriptionDeserializer )
		);

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializerRegistry',
			DVDescriptionDeserializerRegistry::getInstance()
		);
	}

	public function testCanConstructSomeValueDescriptionDeserializer() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DVDescriptionDeserializerRegistry();

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\SomeValueDescriptionDeserializer',
			$instance->getDescriptionDeserializerFor( $dataValue )
		);
	}

	public function testCanConstructTimeValueDescriptionDeserializer() {

		$dataValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DVDescriptionDeserializerRegistry();

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\TimeValueDescriptionDeserializer',
			$instance->getDescriptionDeserializerFor( $dataValue )
		);
	}

	public function testCanConstructRecordValueDescriptionDeserializer() {

		$dataValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DVDescriptionDeserializerRegistry();

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\RecordValueDescriptionDeserializer',
			$instance->getDescriptionDeserializerFor( $dataValue )
		);
	}

	public function testRegisterAdditionalDescriptionDeserializer() {

		$descriptionDeserializer = $this->getMockBuilder( '\SMW\Deserializers\DVDescriptionDeserializer\DescriptionDeserializer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$descriptionDeserializer->expects( $this->once() )
			->method( 'isDeserializerFor' )
			->will( $this->returnValue( true ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DVDescriptionDeserializerRegistry();
		$instance->registerDescriptionDeserializer( $descriptionDeserializer );

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\DescriptionDeserializer',
			$instance->getDescriptionDeserializerFor( $dataValue )
		);
	}

}
