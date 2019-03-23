<?php

namespace SMW\Tests\DataValues\ValueValidators;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\DataValues\ValueValidators\ConstraintSchemaValueValidator;

/**
 * @covers \SMW\DataValues\ValueValidators\ConstraintSchemaValueValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaValueValidatorTest extends \PHPUnit_Framework_TestCase {

	private $dataItemFactory;
	private $dataValueFactory;
	private $schemafinder;

	protected function setUp() {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();

		$this->schemafinder = $this->getMockBuilder( '\SMW\Schema\Schemafinder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintSchemaValueValidator::class,
			new ConstraintSchemaValueValidator( $this->schemafinder )
		);
	}

	public function testHasNoConstraintViolationOnNonRelatedValue() {

		$instance = new ConstraintSchemaValueValidator(
			$this->schemafinder
		);

		$instance->validate( 'Foo' );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function testFetchEmptyConstraintSchemaList() {

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );

		$this->schemafinder->expects( $this->once() )
			->method( 'getConstraintSchema' )
			->with( $this->equalTo( $property ) );

		$dataValue = $this->dataValueFactory->newDataValueByProperty(
			$property
		);

		$instance = new ConstraintSchemaValueValidator(
			$this->schemafinder
		);

		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

}
