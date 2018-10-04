<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\PropertyTableDefinition;
use SMW\StoreFactory;
use SMWDataItem;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\PropertyTableDefinition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertyTableDefinitionTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableDefinition',
			new PropertyTableDefinition( 'foo', 'bar' )
		);
	}

	public function testGetters() {

		$diType = SMWDataItem::TYPE_NUMBER;
		$name   = 'smw_di_number';

		$instance = new PropertyTableDefinition( $diType, $name );

		$this->assertInternalType(
			'array',
			$instance->getFields( StoreFactory::getStore( 'SMWSQLStore3' ) )
		);

		$this->assertEquals(
			$diType,
			$instance->getDiType()
		);

		$this->assertEquals(
			$name,
			$instance->getName()
		);
	}

	public function testIdSubject() {

		$instance = new PropertyTableDefinition( 'foo', 'bar' );
		$instance->setUsesIdSubject( false );

		$this->assertFalse(
			$instance->usesIdSubject()
		);
	}

	public function testGetFixedProperty() {

		$instance = new PropertyTableDefinition( 'foo', 'bar' );

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->getFixedProperty();
	}

}
