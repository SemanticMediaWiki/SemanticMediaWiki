<?php

namespace SMW\Tests\Deserializers;

use SMW\Deserializers\DVDescriptionDeserializerFactory;

/**
 * @covers \SMW\Deserializers\DVDescriptionDeserializerFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class DVDescriptionDeserializerFactoryTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		DVDescriptionDeserializerFactory::getInstance()->clear();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$dispatchingDescriptionDeserializer = $this->getMockBuilder( '\SMW\Deserializers\DVDescriptionDeserializer\DispatchingDescriptionDeserializer' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializerFactory',
			new DVDescriptionDeserializerFactory( $dispatchingDescriptionDeserializer )
		);

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializerFactory',
			DVDescriptionDeserializerFactory::getInstance()
		);
	}

	public function testCanConstructSomeValueDescriptionDeserializer() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DVDescriptionDeserializerFactory();

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\SomeValueDescriptionDeserializer',
			$instance->getDescriptionDeserializerFor( $dataValue )
		);
	}

	public function testCanConstructTimeValueDescriptionDeserializer() {

		$dataValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DVDescriptionDeserializerFactory();

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\TimeValueDescriptionDeserializer',
			$instance->getDescriptionDeserializerFor( $dataValue )
		);
	}

	public function testCanConstructRecordValueDescriptionDeserializer() {

		$dataValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DVDescriptionDeserializerFactory();

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

		$instance = new DVDescriptionDeserializerFactory();
		$instance->registerDescriptionDeserializer( $descriptionDeserializer );

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\DescriptionDeserializer',
			$instance->getDescriptionDeserializerFor( $dataValue )
		);
	}

}
