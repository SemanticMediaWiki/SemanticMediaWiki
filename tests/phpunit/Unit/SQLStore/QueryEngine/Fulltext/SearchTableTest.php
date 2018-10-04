<?php

namespace SMW\Tests\SQLStore\QueryEngine\Fulltext;

use SMW\DataItemFactory;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMWDataItem as DataItem;

/**
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTable
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class SearchTableTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $dataItemFactory;

	protected function setUp() {

		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\QueryEngine\Fulltext\SearchTable',
			new SearchTable( $this->store )
		);
	}

	public function testIsEnabled() {

		$instance = new SearchTable(
			$this->store
		);

		$instance->setEnabled( true );

		$this->assertTrue(
			$instance->isEnabled()
		);
	}

	public function testGetPropertyExemptionList() {

		$instance = new SearchTable(
			$this->store
		);

		$instance->setPropertyExemptionList(
			[ '_TEXT','fo oo' ]
		);

		$this->assertEquals(
			[ '_TEXT', 'fo_oo' ],
			$instance->getPropertyExemptionList()
		);
	}

	public function testIsExemptedProperty() {

		$instance = new SearchTable(
			$this->store
		);

		$instance->setIndexableDataTypes(
			SMW_FT_BLOB | SMW_FT_URI
		);

		$instance->setPropertyExemptionList(
			[ '_TEXT' ]
		);

		$property = $this->dataItemFactory->newDIProperty( '_TEXT' );

		$this->assertTrue(
			$instance->isExemptedProperty( $property )
		);

		$property = $this->dataItemFactory->newDIProperty( 'Foo' );
		$property->setPropertyTypeId( '_uri' );

		$this->assertFalse(
			$instance->isExemptedProperty( $property )
		);
	}

	public function testIsValidType() {

		$instance = new SearchTable(
			$this->store
		);

		$instance->setIndexableDataTypes(
			SMW_FT_BLOB | SMW_FT_URI
		);

		$this->assertTrue(
			$instance->isValidByType( DataItem::TYPE_BLOB )
		);

		$this->assertFalse(
			$instance->isValidByType( DataItem::TYPE_WIKIPAGE )
		);
	}

	public function testHasMinTokenLength() {

		$instance = new SearchTable(
			$this->store
		);

		$instance->setMinTokenSize( 4 );

		$this->assertFalse(
			$instance->hasMinTokenLength( 'bar' )
		);

		$this->assertFalse(
			$instance->hasMinTokenLength( 'テスト' )
		);

		$this->assertTrue(
			$instance->hasMinTokenLength( 'test' )
		);
	}

}
