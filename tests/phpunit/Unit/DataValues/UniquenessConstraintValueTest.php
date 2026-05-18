<?php

namespace SMW\Tests\Unit\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValues\UniquenessConstraintValue;

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

	private $dataItemFactory;

	protected function setUp(): void {
		$this->dataItemFactory = new DataItemFactory();
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
