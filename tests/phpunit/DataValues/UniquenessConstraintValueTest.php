<?php

namespace SMW\Tests\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValues\UniquenessConstraintValue;
use SMW\Property\SpecificationLookup;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\UniquenessConstraintValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class UniquenessConstraintValueTest extends TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			UniquenessConstraintValue::class,
			new UniquenessConstraintValue()
		);
	}

	public function testErrorForMissingFeatureSetting() {
		$instance = new UniquenessConstraintValue();

		$instance->setOption( 'smwgDVFeatures', '' );
		$instance->setUserValue( 'Foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function testErrorForInvalidBoolean() {
		$instance = new UniquenessConstraintValue();

		$instance->setOption( 'smwgDVFeatures', SMW_DV_PVUC );
		$instance->setUserValue( 'Foo' );

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

}
