<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\Query\Language\ValueDescription;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer;
use SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ValueMatchConditionBuilderTest extends TestCase {

	private $textSanitizer;
	private $searchTable;
	private $dataItemFactory;

	protected function setUp(): void {
		$this->dataItemFactory = new DataItemFactory();

		$this->textSanitizer = $this->getMockBuilder( TextSanitizer::class )
			->disableOriginalConstructor()
			->getMock();

		$this->searchTable = $this->getMockBuilder( SearchTable::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ValueMatchConditionBuilder::class,
			new ValueMatchConditionBuilder( $this->textSanitizer, $this->searchTable )
		);
	}

	public function testIsEnabled() {
		$this->searchTable->expects( $this->once() )
			->method( 'isEnabled' )
			->willReturn( false );

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
			->willReturn( 'Foo' );

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
			->willReturn( false );

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
			->willReturn( 'bar' );

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

		$description = $this->getMockBuilder( ValueDescription::class )
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

		$description = $this->getMockBuilder( ValueDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertSame(
			'',
			$instance->getWhereCondition( $description )
		);
	}

}
