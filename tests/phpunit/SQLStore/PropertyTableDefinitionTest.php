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
class PropertyTableDefinitionTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyTableDefinition::class,
			new PropertyTableDefinition( 'foo', 'bar' )
		);
	}

	public function testGetters() {
		$diType = SMWDataItem::TYPE_NUMBER;
		$name   = 'smw_di_number';

		$instance = new PropertyTableDefinition( $diType, $name );

		$this->assertIsArray(

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

		$this->expectException( 'OutOfBoundsException' );
		$instance->getFixedProperty();
	}

	public function testTableType() {
		$instance = new PropertyTableDefinition( 'foo', 'bar' );
		$instance->setTableType( PropertyTableDefinition::TYPE_CORE );

		$this->assertFalse(
			$instance->isTableType( PropertyTableDefinition::TYPE_CUSTOM )
		);
	}

}
