<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\TableDefinition;
use SMW\StoreFactory;
use SMWDataItem;

/**
 * @covers \SMW\SQLStore\TableDefinition
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class TableDefinitionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\TableDefinition',
			new TableDefinition( 'foo', 'bar' )
		);
	}

	public function testGetters() {

		$diType = SMWDataItem::TYPE_NUMBER;
		$name   = 'smw_di_number';

		$instance = new TableDefinition( $diType, $name );

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

		$instance = new TableDefinition( 'foo', 'bar' );
		$instance->setUsesIdSubject( false );

		$this->assertFalse(
			$instance->usesIdSubject()
		);
	}

	public function testGetFixedProperty() {

		$instance = new TableDefinition( 'foo', 'bar' );

		$this->setExpectedException( 'OutOfBoundsException' );
		$instance->getFixedProperty();
	}

}
