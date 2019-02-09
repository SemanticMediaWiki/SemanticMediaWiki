<?php

namespace SMW\Tests\Elastic\QueryEngine\DescriptionInterpreters;

use SMW\Elastic\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter;
use SMW\DIWikiPage;
use SMW\Query\DescriptionFactory;
use SMW\DataItemFactory;
use SMW\Options;

/**
 * @covers \SMW\Elastic\QueryEngine\DescriptionInterpreters\ValueDescriptionInterpreter
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ValueDescriptionInterpreterTest extends \PHPUnit_Framework_TestCase {

	private $conditionBuilder;

	public function setUp() {

		$this->descriptionFactory = new DescriptionFactory();
		$this->dataItemFactory = new DataItemFactory();

		$this->conditionBuilder = $this->getMockBuilder( '\SMW\Elastic\QueryEngine\ConditionBuilder' )
			->disableOriginalConstructor()
			->setMethods( [ 'getID' ] )
			->getMock();

		$this->conditionBuilder->expects( $this->any() )
			->method( 'getID' )
			->will( $this->onConsecutiveCalls( 42, 1001, 9000, 110001 ) );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ValueDescriptionInterpreter::class,
			new ValueDescriptionInterpreter( $this->conditionBuilder )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testInterpretDescription( $dataItem, $comparator, $options, $expected ) {

		$this->conditionBuilder->setOptions( new Options(
			[
				'cjk.best.effort.proximity.match' => true,
				'maximum.value.length' => 500
			]
		) );

		$instance = new ValueDescriptionInterpreter(
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

	public function testRestrictedLength() {

		$options = [
			'property' => $this->dataItemFactory->newDIProperty( 'Bar' ),
			'pid'   => 'P:42',
			'field' => 'wpgID',
			'type'  => 'must'
		];

		$this->conditionBuilder->setOptions( new Options(
			[
				'maximum.value.length' => 1
			]
		) );

		$instance = new ValueDescriptionInterpreter(
			$this->conditionBuilder
		);

		$description = $this->descriptionFactory->newValueDescription(
			$this->dataItemFactory->newDIWikiPage( 'test' ),
			null,
			SMW_CMP_EQ
		);

		$condition = $instance->interpretDescription(
			$description,
			$options
		);

		// 4 vs. 42
		$this->assertEquals(
			'{"bool":{"must":{"terms":{"_id":["4"]}}}}',
			(string)$condition
		);
	}

	public function valueProvider() {

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
			'{"bool":{"must":{"terms":{"_id":[42]}}}}'
		];

		yield [
			$dataItem,
			SMW_CMP_LIKE,
			$options,
			'{"bool":{"must":[{"match":{"subject.sortkey":"test"}}]}}'
		];

		// wide.proximity.fields
		$dataItem = $dataItemFactory->newDIWikiPage( '~*test*' );

		yield [
			$dataItem,
			SMW_CMP_LIKE,
			$options,
			'{"bool":{"must":{"query_string":{"fields":["text_copy"],"query":"*test*","minimum_should_match":1}}}}'
		];

		// wide.proximity.fields
		// cjk.best.effort.proximity.match
		$dataItem = $dataItemFactory->newDIWikiPage( '~*テスト*' );

		yield [
			$dataItem,
			SMW_CMP_LIKE,
			$options,
			'{"bool":{"must":[{"multi_match":{"fields":["text_copy"],"query":"テスト","type":"phrase"}}]}}'
		];
	}


}
