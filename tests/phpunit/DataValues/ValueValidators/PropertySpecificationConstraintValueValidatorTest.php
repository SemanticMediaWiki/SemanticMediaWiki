<?php

namespace SMW\Tests\DataValues\ValueValidators;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\DataValues\ValueValidators\PropertySpecificationConstraintValueValidator;

/**
 * @covers \SMW\DataValues\ValueValidators\PropertySpecificationConstraintValueValidator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertySpecificationConstraintValueValidatorTest extends TestCase {

	private $dataItemFactory;
	private $dataValueFactory;

	protected function setUp(): void {
		parent::setUp();
		$this->dataItemFactory = new DataItemFactory();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertySpecificationConstraintValueValidator::class,
			new PropertySpecificationConstraintValueValidator()
		);
	}

	public function testHasNoConstraintViolationOnNonRelatedValue() {
		$instance = new PropertySpecificationConstraintValueValidator();
		$instance->validate( 'Foo' );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);
	}

	public function testHasNoConstraintViolationOnDisabledPreferredLabelPropertyButWithError() {
		$dataValue = $this->dataValueFactory->newDataValueByProperty(
			$this->dataItemFactory->newDIProperty( '_PPLB' )
		);

		$dataValue->setContextPage(
			$this->dataItemFactory->newDIWikiPage( 'Foo', SMW_NS_PROPERTY )
		);

		$dataValue->setOption( 'smwgDVFeatures', ~SMW_DV_PPLB );

		$instance = new PropertySpecificationConstraintValueValidator();
		$instance->validate( $dataValue );

		$this->assertFalse(
			$instance->hasConstraintViolation()
		);

		$this->assertNotEmpty(
			$dataValue->getErrors()
		);
	}

}
