<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\DataItemFactory;
use SMW\SQLStore\QueryEngine\Fulltext\MySQLValueMatchConditionBuilder;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\MySQLValueMatchConditionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class MySQLValueMatchConditionBuilderTest extends \PHPUnit_Framework_TestCase {

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
			'\SMW\SQLStore\QueryEngine\Fulltext\MySQLValueMatchConditionBuilder',
			new MySQLValueMatchConditionBuilder( $this->textSanitizer, $this->searchTable )
		);
	}

	public function testIsEnabled() {

		$this->searchTable->expects( $this->once() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$instance = new MySQLValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$this->assertTrue(
			$instance->isEnabled()
		);
	}

	public function testGetTableName() {

		$this->searchTable->expects( $this->once() )
			->method( 'getTableName' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new MySQLValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$this->assertEquals(
			'Foo',
			$instance->getTableName()
		);
	}

	public function testGetSortIndexField() {

		$this->searchTable->expects( $this->any() )
			->method( 'getSortField' )
			->will( $this->returnValue( 's_id' ) );

		$instance = new MySQLValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$this->assertEquals(
			'Foo.s_id',
			$instance->getSortIndexField( 'Foo' )
		);
	}

	public function testCanApplyFulltextSearchMatchCondition() {

		$this->searchTable->expects( $this->once() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$this->searchTable->expects( $this->once() )
			->method( 'isValidByType' )
			->will( $this->returnValue( true ) );

		$this->searchTable->expects( $this->once() )
			->method( 'hasMinTokenLength' )
			->will( $this->returnValue( true ) );

		$this->searchTable->expects( $this->once() )
			->method( 'isExemptedProperty' )
			->will( $this->returnValue( false ) );

		$instance = new MySQLValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Foo' ) ) );

		$description->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar' ) ) );

		$description->expects( $this->atLeastOnce() )
			->method( 'getComparator' )
			->will( $this->returnValue( SMW_CMP_LIKE ) );

		$this->assertTrue(
			$instance->canHaveMatchCondition( $description )
		);

		$instance->getWhereCondition( $description );
	}

	/**
	 * @dataProvider searchTermProvider
	 */
	public function testGetWhereConditionWithoutProperty( $text, $indexField, $expected ) {

		$this->textSanitizer->expects( $this->once() )
			->method( 'sanitize' )
			->will( $this->returnValue( $text ) );

		$this->searchTable->expects( $this->any() )
			->method( 'isEnabled' )
			->will( $this->returnValue( true ) );

		$this->searchTable->expects( $this->once() )
			->method( 'getIndexField' )
			->will( $this->returnValue( $indexField ) );

		$this->searchTable->expects( $this->once() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$instance = new MySQLValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar' ) ) );

		$description->expects( $this->once() )
			->method( 'getComparator' )
			->will( $this->returnValue( SMW_CMP_LIKE ) );

		$this->assertEquals(
			$expected,
			$instance->getWhereCondition( $description )
		);
	}

	public function searchTermProvider() {

		$provider[] = [
			'foooo',
			'barColumn',
			"MATCH(barColumn) AGAINST (foooo IN BOOLEAN MODE) "
		];

		$provider[] = [
			'foooo&BOL',
			'barColumn',
			"MATCH(barColumn) AGAINST (foooo IN BOOLEAN MODE) "
		];

		$provider[] = [
			'foooo&INL',
			'barColumn',
			"MATCH(barColumn) AGAINST (foooo IN NATURAL LANGUAGE MODE) "
		];

		$provider[] = [
			'foooo&QEX',
			'barColumn',
			"MATCH(barColumn) AGAINST (foooo WITH QUERY EXPANSION) "
		];

		return $provider;
	}

}
