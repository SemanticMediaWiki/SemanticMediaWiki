<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\DataItemFactory;
use SMW\SQLStore\QueryEngine\Fulltext\SQLiteValueMatchConditionBuilder;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SQLiteValueMatchConditionBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SQLiteValueMatchConditionBuilderTest extends \PHPUnit\Framework\TestCase {

	private $textSanitizer;
	private $searchTable;
	private $dataItemFactory;

	protected function setUp(): void {
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
			'\SMW\SQLStore\QueryEngine\Fulltext\SQLiteValueMatchConditionBuilder',
			new SQLiteValueMatchConditionBuilder( $this->textSanitizer, $this->searchTable )
		);
	}

	public function testIsEnabled() {
		$this->searchTable->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( true );

		$instance = new SQLiteValueMatchConditionBuilder(
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
			->willReturn( 'Foo' );

		$instance = new SQLiteValueMatchConditionBuilder(
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
			->willReturn( 's_id' );

		$instance = new SQLiteValueMatchConditionBuilder(
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
			->willReturn( true );

		$this->searchTable->expects( $this->once() )
			->method( 'isValidByType' )
			->willReturn( true );

		$this->searchTable->expects( $this->once() )
			->method( 'hasMinTokenLength' )
			->willReturn( true );

		$this->searchTable->expects( $this->once() )
			->method( 'isExemptedProperty' )
			->willReturn( false );

		$instance = new SQLiteValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->willReturn( $this->dataItemFactory->newDIProperty( 'Foo' ) );

		$description->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar' ) );

		$description->expects( $this->atLeastOnce() )
			->method( 'getComparator' )
			->willReturn( SMW_CMP_LIKE );

		$this->assertTrue(
			$instance->canHaveMatchCondition( $description )
		);

		$instance->getWhereCondition( $description );
	}

	public function testGetWhereConditionWithPropertyOnTempTable() {
		$this->textSanitizer->expects( $this->once() )
			->method( 'sanitize' )
			->willReturn( 'Foo' );

		$this->searchTable->expects( $this->once() )
			->method( 'getIndexField' )
			->willReturn( 'indexField' );

		$this->searchTable->expects( $this->atLeastOnce() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$instance = new SQLiteValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->willReturn( $this->dataItemFactory->newDIProperty( 'Foo' ) );

		$description->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar' ) );

		$description->expects( $this->once() )
			->method( 'getComparator' )
			->willReturn( SMW_CMP_LIKE );

		$this->assertSame(
			'tempTable.indexField MATCH Foo AND tempTable.p_id=',
			$instance->getWhereCondition( $description, 'tempTable' )
		);
	}

	/**
	 * @dataProvider searchTermProvider
	 */
	public function testGetWhereConditionWithoutProperty( $text, $indexField, $expected ) {
		$this->textSanitizer->expects( $this->once() )
			->method( 'sanitize' )
			->willReturn( $text );

		$this->searchTable->expects( $this->any() )
			->method( 'isEnabled' )
			->willReturn( true );

		$this->searchTable->expects( $this->once() )
			->method( 'getIndexField' )
			->willReturn( $indexField );

		$this->searchTable->expects( $this->once() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$instance = new SQLiteValueMatchConditionBuilder(
			$this->textSanitizer,
			$this->searchTable
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar' ) );

		$description->expects( $this->once() )
			->method( 'getComparator' )
			->willReturn( SMW_CMP_LIKE );

		$this->assertEquals(
			$expected,
			$instance->getWhereCondition( $description )
		);
	}

	public function searchTermProvider() {
		$provider[] = [
			'foooo',
			'barColumn',
			"barColumn MATCH foooo"
		];

		return $provider;
	}

}
