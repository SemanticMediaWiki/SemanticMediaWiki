<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\DataItemFactory;
use SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ValueMatchConditionBuilderTest extends \PHPUnit_Framework_TestCase {

	private $textSanitizer;
	private $searchTable;
	private $dataItemFactory;

	protected function setUp() {

		$this->dataItemFactory = new DataItemFactory();

		$this->textSanitizer = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer' )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTable = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\SearchTable' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder',
			new ValueMatchConditionBuilder( $this->textSanitizer, $this->searchTable )
		);
	}

	public function testIsEnabled() {

		$this->searchTable->expects( $this->once() )
			->method( 'isEnabled' )
			->will( $this->returnValue( false ) );

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$this->assertFalse(
			$instance->isEnabled()
		);
	}

	public function testGetTableName() {

		$this->searchTable->expects( $this->once() )
			->method( 'getTableName' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$this->assertEquals(
			'Foo',
			$instance->getTableName()
		);
	}

	public function testHasMinTokenLength() {

		$this->searchTable->expects( $this->once() )
			->method( 'hasMinTokenLength' )
			->will( $this->returnValue( false ) );

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$this->assertFalse(
			$instance->hasMinTokenLength( 'bar' )
		);
	}

	public function testGetSortIndexField() {

		$this->searchTable->expects( $this->once() )
			->method( 'getSortField' )
			->will( $this->returnValue( 'bar' ) );

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$this->assertEquals(
			'Foo.bar',
			$instance->getSortIndexField( 'Foo' )
		);
	}

	public function testCanApplyFulltextSearchMatchCondition() {

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertFalse(
			$instance->canHaveMatchCondition( $description )
		);
	}

	public function testGetWhereConditionWithPropertyOnTempTable() {

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertSame(
			'',
			$instance->getWhereCondition( $description )
		);
	}

}
