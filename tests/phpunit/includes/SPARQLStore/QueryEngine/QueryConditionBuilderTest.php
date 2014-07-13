<?php

namespace SMW\Tests\SPARQLStore\QueryEngine;

use SMW\Tests\Util\StringBuilder;

use SMW\SPARQLStore\QueryEngine\QueryConditionBuilder;
use SMW\DIProperty;
use SMW\DIWikiPage;

use SMWDIBlob as DIBlob;
use SMWValueDescription as ValueDescription;
use SMWSomeProperty as SomeProperty;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMWThingDescription as ThingDescription;
use SMWConjunction as Conjunction;
use SMWDisjunction as Disjunction;
use SMWClassDescription as ClassDescription;
use SMWNamespaceDescription as NamespaceDescription;

/**
 * @covers \SMW\SPARQLStore\QueryEngine\QueryConditionBuilder
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-sparql
 * @group semantic-mediawiki-query
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class QueryConditionBuilderTest extends \PHPUnit_Framework_TestCase {

	private $stringBuilder;

	protected function setUp() {
		parent::setUp();

		$this->stringBuilder = new StringBuilder();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\QueryConditionBuilder',
			new QueryConditionBuilder()
		);
	}

	public function testQueryForSingleProperty() {

		$property = new DIProperty( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$instance = new QueryConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$this->assertEquals(
			"?result property:Foo ?v1 " . ".\n",
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSinglePropertyWithValue() {

		$description = new ValueDescription(
			new DIBlob( 'SomePropertyValue' ),
			new DIProperty( 'Foo' )
		);

		$instance = new QueryConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\SingletonCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '"SomePropertyValue" swivt:page ?url .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSomePropertyWithValue() {

		$property = new DIProperty( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'SomePropertyBlobValue' ) )
		);

		$instance = new QueryConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$this->assertEquals(
			'?result property:Foo "SomePropertyBlobValue" ' . ".\n",
			$instance->convertConditionToString( $condition )
		);
	}
	public function testQueryForSinglePropertyWithValueComparator() {

		$property = new DIProperty( 'Foo' );

		$description = new SomeProperty(
			$property,
			new ValueDescription( new DIBlob( 'SomePropertyBlobValue' ), null, SMW_CMP_LEQ )
		);

		$instance = new QueryConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( 'FILTER( ?v1 <= "SomePropertyBlobValue" )' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSingleCategory() {

		$description = new ClassDescription(
			new DIWikiPage( 'Foo', NS_CATEGORY, '' )
		);

		$instance = new QueryConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( "{ ?result rdf:type wiki:Category-3AFoo . }" )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForSingleNamespace() {

		$description = new NamespaceDescription( NS_HELP );

		$instance = new QueryConditionBuilder();

		$condition = $instance->buildCondition( $description );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$this->assertSame( 12, NS_HELP );

		$expectedConditionString = $this->stringBuilder
			->addString( '{ ?result swivt:wikiNamespace "12"^^xsd:integer . }' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyConjunction () {

		$conjunction = new Conjunction( array(
			new SomeProperty( new DIProperty( 'Foo' ), new ValueDescription( new DIBlob( 'SomePropertyValue' ) ) ),
			new SomeProperty( new DIProperty( 'Bar' ), new ThingDescription() ),
		) );

		$instance = new QueryConditionBuilder();

		$condition = $instance->buildCondition( $conjunction );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '?result property:Foo "SomePropertyValue" .' )->addNewLine()
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

	public function testQueryForPropertyDisjunction () {

		$conjunction = new Disjunction( array(
			new SomeProperty( new DIProperty( 'Foo' ), new ThingDescription() ),
			new SomeProperty( new DIProperty( 'Bar' ), new ThingDescription() )
		) );

		$instance = new QueryConditionBuilder();

		$condition = $instance->buildCondition( $conjunction );

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\QueryEngine\Condition\WhereCondition',
			$condition
		);

		$expectedConditionString = $this->stringBuilder
			->addString( '{' )->addNewLine()
			->addString( '?result property:Foo ?v1 .' )->addNewLine()
			->addString( '} UNION {' )->addNewLine()
			->addString( '?result property:Bar ?v2 .' )->addNewLine()
			->addString( '}' )
			->getString();

		$this->assertEquals(
			$expectedConditionString,
			$instance->convertConditionToString( $condition )
		);
	}

}
