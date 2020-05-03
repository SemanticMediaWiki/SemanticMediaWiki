<?php

namespace SMW\Tests\Integration\Schema;

use SMW\Schema\SchemaDefinition;
use SMW\Schema\Compartment;
use SMW\Schema\CompartmentIterator;
use SMW\Schema\SchemaList;
use SMW\Schema\SchemaFilterFactory;
use SMW\Schema\Filters\CategoryFilter;
use SMW\Schema\Filters\CompositeFilter;
use SMW\Schema\SchemaFilter;
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

	protected function setUp() : void {
		parent::setUp();

		$this->schemaList = new SchemaList( [] );

		$schemata = [
			'fake_namespace_category_rule_schema_1',
			'fake_namespace_category_rule_schema_2',
			'fake_namespace_category_unnamed_rule_schema_3',
			'fake_namespace_category_action_rule_schema_4',
			'fake_namespace_category_property_action_rule_schema_6'

		];

		foreach ( $schemata as $name ) {
			$this->schemaList->add( $this->newSchemaDefinition( $name ) );
		}
	}

	private function newSchemaDefinition( $name ) {
		return new SchemaDefinition(
			$name,
			json_decode( file_get_contents( SMW_PHPUNIT_DIR . "/Fixtures/Schema/$name.json" ), true )
		);
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

	public function testNamespaceCategoryFilter_FindBestFilter() {

		$schemaList = new SchemaList(
			[
				$this->newSchemaDefinition( 'fake_namespace_category_rule_schema_best_sort_5' )
			]
		);

		$compartments = $schemaList->newCompartmentIteratorByKey(
			'filter_rules',
			CompartmentIterator::RULE_COMPARTMENT
		);

		$schemaFilterFactory = new SchemaFilterFactory();

		$categoryFilter = $schemaFilterFactory->newCategoryFilter(
			[ 'Foo' ]
		);

		$namespaceFilter = $schemaFilterFactory->newNamespaceFilter(
			NS_MAIN
		);

		$namespaceFilter->addOption( SchemaFilter::FILTER_CONDITION_NOT_REQUIRED, true );

		$compositeFilter = $schemaFilterFactory->newCompositeFilter(
			[
				$categoryFilter,
				$namespaceFilter
			]
		);

		$compositeFilter->filter( $compartments );
		$compositeFilter->sortMatches( CompositeFilter::SORT_FILTER_SCORE );

		$sections = [];

		foreach ( $compositeFilter->getMatches() as $compartment ) {
			$sections[] = $compartment->get( Compartment::ASSOCIATED_SECTION );
		}

		$this->assertEquals(
			[ 'rule_5_2', 'rule_5_3' ],
			$sections
		);
	}

	public function testNamespaceCategoryFilter_FindBestFilter_ReverseComposite() {

		$schemaList = new SchemaList(
			[
				$this->newSchemaDefinition( 'fake_namespace_category_rule_schema_best_sort_5' )
			]
		);

		$compartments = $schemaList->newCompartmentIteratorByKey(
			'filter_rules',
			CompartmentIterator::RULE_COMPARTMENT
		);

		$schemaFilterFactory = new SchemaFilterFactory();

		$categoryFilter = $schemaFilterFactory->newCategoryFilter(
			[ 'Foo' ]
		);

		$namespaceFilter = $schemaFilterFactory->newNamespaceFilter(
			NS_MAIN
		);

		$namespaceFilter->addOption( SchemaFilter::FILTER_CONDITION_NOT_REQUIRED, true );

		$compositeFilter = $schemaFilterFactory->newCompositeFilter(
			[
				$namespaceFilter,
				$categoryFilter
			]
		);

		$compositeFilter->filter( $compartments );
		$compositeFilter->sortMatches( CompositeFilter::SORT_FILTER_SCORE );

		$sections = [];

		foreach ( $compositeFilter->getMatches() as $compartment ) {
			$sections[] = $compartment->get( Compartment::ASSOCIATED_SECTION );
		}

		$this->assertEquals(
			[ 'rule_5_2', 'rule_5_3' ],
			$sections
		);
	}

	/**
	 * @dataProvider namespaceCategoryPropertyFilterProvider
	 */
	public function testNamespaceCategoryPropertyFilterProvider( $ns, $categories, $properties, $expected ) {

		$compartments = $this->schemaList->newCompartmentIteratorByKey(
			'filter_rules'
		);

		$schemaFilterFactory = new SchemaFilterFactory();

		$namespaceFilter = $schemaFilterFactory->newNamespaceFilter(
			$ns
		);

		$categoryFilter = $schemaFilterFactory->newCategoryFilter(
			$categories
		);

		$propertyFilter = $schemaFilterFactory->newPropertyFilter(
			$properties
		);

		$compositeFilter = $schemaFilterFactory->newCompositeFilter(
			[
				$namespaceFilter,
				$categoryFilter,
				$propertyFilter,
			]
		);

		$compositeFilter->filter( $compartments );
		$compositeFilter->sortMatches( CompositeFilter::SORT_FILTER_SCORE );

		$sections = [];

		foreach ( $compositeFilter->getMatches() as $compartment ) {
			$sections[] = $compartment->get( Compartment::ASSOCIATED_SECTION );
		}

		$this->assertEquals(
			$expected,
			$sections
		);
	}

	public function namespaceCategoryPropertyFilterProvider() {

		yield "'property-6-a', NS_MAIN" => [
			NS_MAIN,
			[],
			[ new DIProperty( 'property-6-a' ) ],
			[]
		];

		yield "'property-6-a', 'property-6-b' NS_MAIN" => [
			NS_MAIN,
			function() {
				return [ 'category-6-a' ];
			},
			function(){
				return [ new DIProperty( 'property-6-a' ), new DIProperty( 'property-6-b' ) ];
			},
			[ 'rule_6_2' ]
		];
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

}
