<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\ValueValidatorRegistry;

/**
 * @covers \SMW\DataValues\ValueValidatorRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class ValueValidatorRegistryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueValidatorRegistry',
			new ValueValidatorRegistry()
		);

		ValueValidatorRegistry::clear();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueValidatorRegistry',
			ValueValidatorRegistry::getInstance()
		);

		ValueValidatorRegistry::clear();
	}

	public function testGetConstraintValueValidator() {

		$instance = new ValueValidatorRegistry();

		$this->assertInstanceOf(
			'\SMW\DataValues\ValueValidators\ConstraintValueValidator',
			$instance->getConstraintValueValidator()
		);
	}

}
