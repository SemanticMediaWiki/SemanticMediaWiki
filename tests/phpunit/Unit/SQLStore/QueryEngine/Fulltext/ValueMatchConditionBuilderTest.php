<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder;
use SMW\DataItemFactory;

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
	private $dataItemFactory;

	protected function setUp() {

		$this->dataItemFactory = new DataItemFactory();

		$this->textSanitizer = $this->getMockBuilder( '\SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder',
			new ValueMatchConditionBuilder( $this->textSanitizer )
		);
	}

	public function testIsEnabled() {

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer
		);

		$this->assertFalse(
			$instance->isEnabled()
		);
	}

	public function testGetTableName() {

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer
		);

		$this->assertEquals(
			'',
			$instance->getTableName()
		);
	}

	public function testHasMinTokenLength() {

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer
		);

		$this->assertFalse(
			$instance->hasMinTokenLength( 'bar' )
		);
	}

	public function testGetSortIndexField() {

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer
		);

		$this->assertEquals(
			'',
			$instance->getSortIndexField( 'Foo' )
		);
	}

	public function testCanApplyFulltextSearchMatchCondition() {

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer
		);

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertFalse(
			$instance->canApplyFulltextSearchMatchCondition( $description )
		);
	}

	public function testGetWhereConditionWithPropertyOnTempTable() {

		$instance = new ValueMatchConditionBuilder(
			$this->textSanitizer
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
