<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\PropertyTableDefinitionBuilder;
use SMW\Tests\Utils\MwHooksHandler;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\SQLStore\PropertyTableDefinitionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class PropertyTableDefinitionBuilderTest extends \PHPUnit_Framework_TestCase {

	private $propertyTypeFinder;
	private $mwHooksHandler;

	protected function setUp() {
		parent::setUp();

		$this->mwHooksHandler = new MwHooksHandler();
		$this->mwHooksHandler->deregisterListedHooks();

		$this->propertyTypeFinder = $this->getMockBuilder( '\SMW\SQLStore\PropertyTypeFinder' )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown() {

		$this->mwHooksHandler->restoreListedHooks();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$dataItems = [];
		$specials = [];
		$fixed = [];

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableDefinitionBuilder',
			new PropertyTableDefinitionBuilder( $this->propertyTypeFinder )
		);
	}

	public function testDataItemTypes() {

		$dataItems = [ DataItem::TYPE_NUMBER => 'smw_di_number' ];
		$specials = [];
		$fixed = [];

		$instance = new PropertyTableDefinitionBuilder(
			$this->propertyTypeFinder
		);

		$instance->doBuild(
			$dataItems,
			$specials,
			$fixed
		);

		$definition = $instance->newTableDefinition(
			DataItem::TYPE_NUMBER, 'smw_di_number'
		);

		$expected = [
			'smw_di_number' => $definition
		];

		$this->assertEquals(
			$expected,
			$instance->getTableDefinitions()
		);
	}

	public function testUserDefinedFixedPropertyDeclaration() {

		$propertyKey = 'foo bar';
		$expectedKey = 'Foo_bar';

		$dataItems = [];
		$specials = [];
		$fixed = [ $propertyKey ];

		$this->propertyTypeFinder->expects( $this->any() )
			->method( 'findTypeID' )
			->will( $this->returnValue( '_num' ) );

		$instance = new PropertyTableDefinitionBuilder(
			$this->propertyTypeFinder
		);

		$instance->doBuild(
			$dataItems,
			$specials,
			$fixed
		);

		$tableName = $instance->createHashedTableNameFrom( $expectedKey );
		$definition = $instance->newTableDefinition( DataItem::TYPE_NUMBER, $tableName, $expectedKey );

		$expected = [
			'definition' => [ $tableName => $definition ],
			'tableId' => [ $expectedKey => $tableName, '_SKEY' => null ]
		];

		$this->assertEquals(
			$expected['definition'],
			$instance->getTableDefinitions()
		);

		$this->assertEquals(
			$expected['tableId'],
			$instance->getFixedPropertyTableIds()
		);
	}

	public function testSpecialProperties() {

		$propertyKey = '_MDAT';

		$dataItems = [];
		$specials = [ $propertyKey ];
		$fixed = [];

		$instance = new PropertyTableDefinitionBuilder(
			$this->propertyTypeFinder
		);

		$instance->doBuild(
			$dataItems,
			$specials,
			$fixed
		);

		$tableName = $instance->getTablePrefix() . strtolower( $propertyKey );
		$definition = $instance->newTableDefinition( DataItem::TYPE_TIME, $tableName, $propertyKey );
		$expected = [ $tableName => $definition ];

		$this->assertEquals(
			$expected,
			$instance->getTableDefinitions()
		);
	}

	public function testRedirects() {

		$propertyKey = '_REDI';

		$dataItems = [];
		$specials = [ $propertyKey ];
		$fixed = [];

		$instance = new PropertyTableDefinitionBuilder(
			$this->propertyTypeFinder
		);

		$instance->doBuild(
			$dataItems,
			$specials,
			$fixed
		);

		$tableName = $instance->getTablePrefix() . strtolower( $propertyKey );
		$definition = $instance->newTableDefinition( DataItem::TYPE_WIKIPAGE, $tableName, $propertyKey );

		$expected = [ $tableName => $definition ];
		$tableDefinitions = $instance->getTableDefinitions();

		$this->assertFalse(
			$tableDefinitions[$tableName]->usesIdSubject()
		);
	}

}
