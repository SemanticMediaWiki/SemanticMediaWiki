<?php

namespace SMW\Tests\Query;

use PHPUnit\Framework\TestCase;
use SMW\Query\DescriptionBuilderRegistry;
use SMW\Query\DescriptionBuilders\DescriptionBuilder;
use SMW\Query\DescriptionBuilders\NumberValueDescriptionBuilder;
use SMW\Query\DescriptionBuilders\RecordValueDescriptionBuilder;
use SMW\Query\DescriptionBuilders\SomeValueDescriptionBuilder;
use SMW\Query\DescriptionBuilders\TimeValueDescriptionBuilder;

/**
 * @covers \SMW\Query\DescriptionBuilderRegistry
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class DescriptionBuilderRegistryTest extends TestCase {

	protected function tearDown(): void {
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
			SomeValueDescriptionBuilder::class,
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

	public function testCanConstructTimeValueDescriptionBuilder() {
		$dataValue = $this->getMockBuilder( '\SMWTimeValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionBuilderRegistry();

		$this->assertInstanceOf(
			TimeValueDescriptionBuilder::class,
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

	public function testCanConstructNumberValueDescriptionBuilder() {
		$dataValue = $this->getMockBuilder( '\SMWNumberValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionBuilderRegistry();

		$this->assertInstanceOf(
			NumberValueDescriptionBuilder::class,
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

	public function testCanConstructRecordValueDescriptionBuilder() {
		$dataValue = $this->getMockBuilder( '\SMWRecordValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionBuilderRegistry();

		$this->assertInstanceOf(
			RecordValueDescriptionBuilder::class,
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

	public function testRegisterAdditionalDescriptionBuilder() {
		$descriptionBuilder = $this->getMockBuilder( DescriptionBuilder::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isBuilderFor' ] )
			->getMockForAbstractClass();

		$descriptionBuilder->expects( $this->once() )
			->method( 'isBuilderFor' )
			->willReturn( true );

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionBuilderRegistry();
		$instance->registerDescriptionBuilder( $descriptionBuilder );

		$this->assertInstanceOf(
			DescriptionBuilder::class,
			$instance->getDescriptionBuilder( $dataValue )
		);
	}

}
