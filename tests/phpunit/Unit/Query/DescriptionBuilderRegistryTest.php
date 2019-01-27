<?php

namespace SMW\Tests\Query;

use SMW\Query\DescriptionBuilderRegistry;

/**
 * @covers \SMW\Query\DescriptionBuilderRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class DescriptionBuilderRegistryTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			DescriptionBuilderRegistry::class,
			new DescriptionBuilderRegistry()
		);
	}

	public function testCanConstructSomeValueDescriptionBuilder() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionBuilderRegistry();

		$this->assertInstanceOf(
			'\SMW\Query\DescriptionBuilders\SomeValueDescriptionBuilder',
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

	public function testCanConstructTimeValueDescriptionBuilder() {

		$dataValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionBuilderRegistry();

		$this->assertInstanceOf(
			'\SMW\Query\DescriptionBuilders\TimeValueDescriptionBuilder',
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

	public function testCanConstructNumberValueDescriptionBuilder() {

		$dataValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionBuilderRegistry();

		$this->assertInstanceOf(
			'\SMW\Query\DescriptionBuilders\NumberValueDescriptionBuilder',
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

	public function testCanConstructRecordValueDescriptionBuilder() {

		$dataValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionBuilderRegistry();

		$this->assertInstanceOf(
			'\SMW\Query\DescriptionBuilders\RecordValueDescriptionBuilder',
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

	public function testRegisterAdditionalDescriptionBuilder() {

		$descriptionBuilder = $this->getMockBuilder( '\SMW\Query\DescriptionBuilders\DescriptionBuilder' )
			->disableOriginalConstructor()
			->setMethods( [ 'isBuilderFor' ] )
			->getMockForAbstractClass();

		$descriptionBuilder->expects( $this->once() )
			->method( 'isBuilderFor' )
			->will( $this->returnValue( true ) );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionBuilderRegistry();
		$instance->registerDescriptionBuilder( $descriptionBuilder );

		$this->assertInstanceOf(
			'\SMW\Query\DescriptionBuilders\DescriptionBuilder',
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

}
