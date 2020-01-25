<?php

namespace SMW\Tests\Integration\Schema;

use SMW\Schema\SchemaDefinition;
use SMW\Schema\Compartment;
use SMW\Schema\SchemaList;
use SMW\Schema\SchemaFilterFactory;
use SMW\Schema\Filters\CategoryFilter;
use SMW\DIWikiPage;
use SMW\DIProperty;

/**
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FilteredSchemaListTest extends \PHPUnit_Framework_TestCase {

	private $schemaList;

	protected function setUp() {
		parent::setUp();

		$this->schemaList = new SchemaList( [] );

		$schemata = [
			'fake_namespace_category_rule_schema_1',
			'fake_namespace_category_rule_schema_2',
			'fake_namespace_category_unnamed_rule_schema_3',
			'fake_namespace_category_action_rule_schema_4'

		];

		foreach ( $schemata as $name ) {

			$schema = new SchemaDefinition(
				$name,
				json_decode( file_get_contents( SMW_PHPUNIT_DIR . "/Fixtures/Schema/$name.json" ), true )
			);

			$this->schemaList->add( $schema );
		}
	}

	/**
	 * @dataProvider namespaceCategoryFilterProvider
	 */
	public function testNamespaceCategoryFilter( $ns, $categories, $expected ) {

		$compartments = $this->schemaList->newCompartmentIteratorByKey(
			'filter_rules'
		);

		$schemaFilterFactory = new SchemaFilterFactory();

		$categoryFilter = $schemaFilterFactory->newCategoryFilter(
			$categories
		);

		$namespaceFilter = $schemaFilterFactory->newNamespaceFilter(
			$ns
		);

		$namespaceFilter->setNodeFilter(
			$categoryFilter
		);

		$namespaceFilter->filter( $compartments );
		$sections = [];

		foreach ( $namespaceFilter->getMatches() as $compartment ) {
			$sections[] = $compartment->get( Compartment::ASSOCIATED_SECTION );
		}

		$this->assertEquals(
			$expected,
			$sections
		);
	}

	public function testCategoryFilter() {

		$expected = [
			"if" => [ "category" => "Brown fox" ], "then" => [ "action" => "2_4" ],
			'___assoc_schema'  => 'fake namespace category rule schema 2',
			'___assoc_section' => 'rule_2_4'
		];

		$compartments = $this->schemaList->newCompartmentIteratorByKey(
			'filter_rules'
		);

		$schemaFilterFactory = new SchemaFilterFactory();

		$categoryFilter = $schemaFilterFactory->newCategoryFilter(
			[ "Brown fox" ]
		);

		$categoryFilter->filter( $compartments );
		$matches = $categoryFilter->getMatches();

		$this->assertEquals(
			$expected,
			json_decode( $matches[0], true )
		);
	}

	public function namespaceCategoryFilterProvider() {

		yield "NS_MAIN" => [
			NS_MAIN,
			[],
			[ 'rule_1_1', 'rule_1_2' ]
		];

		yield "NS_MAIN, category Foo" => [
			NS_MAIN,
			[ 'Foo' ],
			[ 'rule_1_3', 'rule_2_2', 1 ]
		];

		/**
		 * {
		 *	"if": {
		 *		"category": "Foo"
		 *	},
		 *	"then": {
		 *		"action": "2_3"
		 *	}
		 *},
		 */
		yield "category Foo" => [
			null,
			[ 'Foo' ],
			[ 'rule_2_3', 2 ]
		];

		yield "category Foo, Bar" => [
			null,
			[ 'Foo', 'Bar' ],
			[ 'rule_2_3', 2 /* unnamed_rule_schema_3 */ ]
		];

		/**
		 * {
		 *	"if": {
		 *		"namespace": "NS_TEMPLATE",
		 *		"category": { "allOf" : [ "Foo", "Bar", "Foobar-1" ] }
		 *	},
		 *	"then": {
		 *		"action": "3_4"
		 *	}
		 *},
		 */
		yield "category Foo, Bar, Foobar-1" => [
			NS_TEMPLATE,
			[ 'Foo', 'Bar', 'Foobar-1' ],
			[ 3 /* unnamed_rule_schema_3 */ ]
		];

	}

	public function multi2ChainFilterProvider() {

		yield "NS_MAIN" => [
			NS_MAIN,
			[],
			2
		];

		yield "NS_MAIN, category Foo" => [
			NS_MAIN,
			[ 'Foo' ],
			6
		];

		/**
		 * {
		 *	"if": {
		 *		"category": "Foo"
		 *	},
		 *	"then": {
		 *		"action": "3_3"
		 *	}
		 *},
		 */
		yield "category Foo" => [
			null,
			[ 'Foo' ],
			2
		];

		yield "category Foo, Bar" => [
			null,
			[ 'Foo', 'Bar' ],
			2
		];


		yield "category Foo, Bar, Foobar" => [
			NS_TEMPLATE,
			[ 'Foo', 'Bar', 'Foobar-1' ],
			3
		];
	}

}
