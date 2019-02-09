<?php

namespace SMW\Tests\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\DescriptionInterpreters\SomeValueInterpreter;
use SMW\DIWikiPage;
use SMW\Options;
use SMW\Query\DescriptionFactory;
use SMW\DataItemFactory;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\QueryEngine\DescriptionInterpreters\SomeValueInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SomeValueInterpreterTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $conditionBuilder;
	private $descriptionFactory;
	private $dataItemFactory;

	public function setUp() {

		$this->descriptionFactory = new DescriptionFactory();
		$this->dataItemFactory = new DataItemFactory();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->setMethods( [ 'getID' ] )
			->getMock();

		$this->conditionBuilder->setOptions( new Options(
			[
				'maximum.value.length' => 500
			]
		) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			SomeValueInterpreter::class,
			new SomeValueInterpreter( $this->conditionBuilder )
		);
	}

	public function testInterpretDescription_MissingPropertyThrowsException() {

		$instance = new SomeValueInterpreter(
			$this->conditionBuilder
		);

		$options = [];

		$description = $this->descriptionFactory->newValueDescription(
			$this->dataItemFactory->newDIWikiPage( 'Foo' )
		);

		$this->setExpectedException( '\RuntimeException' );
		$instance->interpretDescription( $description, $options );
	}

	public function testInterpretDescription_MissingPIDThrowsException() {

		$instance = new SomeValueInterpreter(
			$this->conditionBuilder
		);

		$options = [
			'property' => $this->dataItemFactory->newDIProperty( 'Bar' )
		];

		$description = $this->descriptionFactory->newValueDescription(
			$this->dataItemFactory->newDIWikiPage( 'Foo' )
		);

		$this->setExpectedException( '\RuntimeException' );
		$instance->interpretDescription( $description, $options );
	}

	/**
	 * @dataProvider numberValueProvider
	 */
	public function testInterpretDescription_NumberValue( $dataItem, $comparator, $options, $expected ) {

		$instance = new SomeValueInterpreter(
			$this->conditionBuilder
		);

		$description = $this->descriptionFactory->newValueDescription(
			$dataItem,
			null,
			$comparator
		);

		$condition = $instance->interpretDescription(
			$description,
			$options
		);

		$this->assertEquals(
			$expected,
			(string)$condition
		);
	}

	/**
	 * @dataProvider timeValueProvider
	 */
	public function testInterpretDescription_TimeValue( $dataItem, $comparator, $options, $expected ) {

		$instance = new SomeValueInterpreter(
			$this->conditionBuilder
		);

		$description = $this->descriptionFactory->newValueDescription(
			$dataItem,
			null,
			$comparator
		);

		$condition = $instance->interpretDescription(
			$description,
			$options
		);

		$this->assertEquals(
			$expected,
			(string)$condition
		);
	}

	/**
	 * @dataProvider textValueProvider
	 */
	public function testInterpretDescription_TextValue( $dataItem, $comparator, $options, $expected ) {

		$instance = new SomeValueInterpreter(
			$this->conditionBuilder
		);

		$description = $this->descriptionFactory->newValueDescription(
			$dataItem,
			null,
			$comparator
		);

		$condition = $instance->interpretDescription(
			$description,
			$options
		);

		$this->assertEquals(
			$expected,
			(string)$condition
		);
	}

	/**
	 * @dataProvider pageValueProvider
	 */
	public function testInterpretDescription_PageValue( $dataItem, $comparator, $options, $expected ) {

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getID' )
			->will( $this->onConsecutiveCalls( 42, 1001, 9000, 110001 ) );

		$this->conditionBuilder->setOptions( new Options(
			[
				'cjk.best.effort.proximity.match' => true,
				'maximum.value.length' => 500
			]
		) );

		$instance = new SomeValueInterpreter(
			$this->conditionBuilder
		);

		$description = $this->descriptionFactory->newValueDescription(
			$dataItem,
			null,
			$comparator
		);

		$condition = $instance->interpretDescription(
			$description,
			$options
		);

		$this->assertEquals(
			$expected,
			(string)$condition
		);
	}

	public function numberValueProvider() {

		$dataItemFactory = new DataItemFactory();

		$options = [
			'property' => $dataItemFactory->newDIProperty( 'Bar' ),
			'pid'   => 'P:42',
			'field' => 'numField',
			'type'  => 'must'
		];

		$dataItem = $dataItemFactory->newDINumber( 123 );

		yield [
			$dataItem,
			SMW_CMP_EQ,
			$options,
			'{"bool":{"filter":{"term":{"P:42.numField":123}}}}'
		];

		yield [
			$dataItem,
			SMW_CMP_NEQ,
			$options,
			'{"bool":{"must_not":{"term":{"P:42.numField":123}}}}'
		];

		yield [
			$dataItem,
			SMW_CMP_LESS,
			$options,
			'{"bool":{"must":[{"range":{"P:42.numField":{"lt":123}}}]}}'
		];

		yield [
			$dataItem,
			SMW_CMP_GRTR,
			$options,
			'{"bool":{"must":[{"range":{"P:42.numField":{"gt":123}}}]}}'
		];

		yield [
			$dataItem,
			SMW_CMP_LEQ,
			$options,
			'{"bool":{"must":[{"range":{"P:42.numField":{"lte":123}}}]}}'
		];

		yield [
			$dataItem,
			SMW_CMP_GEQ,
			$options,
			'{"bool":{"must":[{"range":{"P:42.numField":{"gte":123}}}]}}'
		];

		// This form is actually handled differently using a compound
		// [[Has number::~123]] -> [[Has number:: <q>[[≥0]] [[≤123]]</q> ]]
		yield [
			$dataItem,
			SMW_CMP_LIKE,
			$options,
			'{"bool":{"must":[{"match":{"P:42.numField":{"query":123,"operator":"and"}}}]}}'
		];

		yield [
			$dataItem,
			SMW_CMP_NLKE,
			$options,
			'{"bool":{"must_not":[{"match":{"P:42.numField":{"query":123,"operator":"and"}}}]}}'
		];
	}

	public function timeValueProvider() {

		$dataItemFactory = new DataItemFactory();

		$options = [
			'property' => $dataItemFactory->newDIProperty( 'Bar' ),
			'pid'   => 'P:42',
			'field' => 'datField',
			'type'  => 'must'
		];

		$dataItem = $dataItemFactory->newDITime( 1, 1970, 12, 12, 12, 12, 12 );

		yield [
			$dataItem,
			SMW_CMP_EQ,
			$options,
			'{"bool":{"filter":{"term":{"P:42.datField":2440933.0084722}}}}'
		];

		yield [
			$dataItem,
			SMW_CMP_NEQ,
			$options,
			'{"bool":{"must_not":{"term":{"P:42.datField":2440933.0084722}}}}'
		];
	}

	public function textValueProvider() {

		$dataItemFactory = new DataItemFactory();

		$options = [
			'property' => $dataItemFactory->newDIProperty( 'Bar' ),
			'pid'   => 'P:42',
			'field' => 'txtField',
			'type'  => 'must'
		];

		$dataItem = $dataItemFactory->newDIBlob( '*test*' );

		yield [
			$dataItem,
			SMW_CMP_EQ,
			$options,
			'{"bool":{"filter":{"term":{"P:42.txtField.keyword":"*test*"}}}}'
		];

		yield [
			$dataItem,
			SMW_CMP_LIKE,
			$options,
			'{"bool":{"must":{"query_string":{"fields":["P:42.txtField","P:42.txtField.keyword"],"query":"*test*"}}}}'
		];
	}

	public function pageValueProvider() {

		$dataItemFactory = new DataItemFactory();

		$options = [
			'property' => $dataItemFactory->newDIProperty( 'Bar' ),
			'pid'   => 'P:42',
			'field' => 'wpgID',
			'type'  => 'must'
		];

		$dataItem = $dataItemFactory->newDIWikiPage( 'test' );

		yield [
			$dataItem,
			SMW_CMP_EQ,
			$options,
			'{"bool":{"filter":{"term":{"P:42.wpgID":42}}}}'
		];

		$options = [
			'property' => $dataItemFactory->newDIProperty( 'Bar' ),
			'pid'   => 'P:42',
			'field' => 'wpgField',
			'type'  => 'must'
		];

		yield [
			$dataItem,
			SMW_CMP_LIKE,
			$options,
			'{"bool":{"must":{"query_string":{"fields":["P:42.wpgField"],"query":"+test"}}}}'
		];

		$dataItem = $dataItemFactory->newDIWikiPage( '*テスト*' );

		yield [
			$dataItem,
			SMW_CMP_LIKE,
			$options,
			'{"bool":{"must":[{"match_phrase":{"P:42.wpgField":"テスト"}}]}}'
		];

	}

}
