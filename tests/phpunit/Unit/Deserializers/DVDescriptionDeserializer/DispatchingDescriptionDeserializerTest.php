<?php

namespace SMW\Tests\Deserializers\DVDescriptionDeserializer;

use SMW\Deserializers\DVDescriptionDeserializer\DispatchingDescriptionDeserializer;

/**
 * @covers \SMW\Deserializers\DVDescriptionDeserializer\DispatchingDescriptionDeserializer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class DispatchingDescriptionDeserializerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\DispatchingDescriptionDeserializer',
			new DispatchingDescriptionDeserializer()
		);
	}

	public function testGetDescriptionDeserializerForMatchableDataValue() {

		$descriptionDeserializer = $this->getMockBuilder( '\SMW\Deserializers\DVDescriptionDeserializer\DescriptionDeserializer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$descriptionDeserializer->expects( $this->once() )
			->method( 'isDeserializerFor' )
			->will( $this->returnValue( true ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDescriptionDeserializer();
		$instance->addDescriptionDeserializer( $descriptionDeserializer );

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\DescriptionDeserializer',
			$instance->getDescriptionDeserializerFor( $dataValue )
		);
	}

	public function testGetDefaultDescriptionDeserializerForMatchableDataValue() {

		$descriptionDeserializer = $this->getMockBuilder( '\SMW\Deserializers\DVDescriptionDeserializer\DescriptionDeserializer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$descriptionDeserializer->expects( $this->once() )
			->method( 'isDeserializerFor' )
			->will( $this->returnValue( true ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDescriptionDeserializer();
		$instance->addDefaultDescriptionDeserializer( $descriptionDeserializer );

		$this->assertInstanceOf(
			'\SMW\Deserializers\DVDescriptionDeserializer\DescriptionDeserializer',
			$instance->getDescriptionDeserializerFor( $dataValue )
		);
	}

	public function testTryToGetDescriptionDeserializerForNonDispatchableDataValueThrowsException() {

		$descriptionDeserializer = $this->getMockBuilder( '\SMW\Deserializers\DVDescriptionDeserializer\DescriptionDeserializer' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$descriptionDeserializer->expects( $this->once() )
			->method( 'isDeserializerFor' )
			->will( $this->returnValue( false ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DispatchingDescriptionDeserializer();
		$instance->addDescriptionDeserializer( $descriptionDeserializer );

		$this->setExpectedException( 'RuntimeException' );
		$instance->getDescriptionDeserializerFor( $dataValue );
	}

}
