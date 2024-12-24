<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\DataItemFactory;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class PropertyLabelSimilarityLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;
	private $dataItemFactory;

	protected function setUp(): void {
		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup',
			new PropertyLabelSimilarityLookup( $this->store )
		);
	}

	public function testGetPropertyMaxCount() {
		$this->store->expects( $this->any() )
			->method( 'getStatistics' )
			->willReturn( [ 'TOTALPROPS' => 42 ] );

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyLabelSimilarityLookup(
			$this->store,
			$propertySpecificationLookup
		);

		$this->assertEquals(
			42,
			$instance->getPropertyMaxCount()
		);
	}

	public function testCompareAndFindLabels() {
		$row = new \stdClass;
		$row->smw_title = 'Foo';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ $row ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyLabelSimilarityLookup(
			$this->store,
			$propertySpecificationLookup
		);

		$requestOptions = new RequestOptions();

		$instance->compareAndFindLabels( $requestOptions );

		$this->assertSame(
			1,
			$instance->getLookupCount()
		);
	}

	public function testCompareAndFindLabelsWithExemption() {
		$row1 = new \stdClass;
		$row1->smw_title = 'Foo';

		$row2 = new \stdClass;
		$row2->smw_title = 'Foobar';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ $row1, $row2 ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->willReturn( [ $this->dataItemFactory->newDIWikiPage( 'Foobar', SMW_NS_PROPERTY ) ] );

		$instance = new PropertyLabelSimilarityLookup(
			$this->store,
			$propertySpecificationLookup
		);

		$requestOptions = new RequestOptions();

		$instance->setExemptionProperty( 'Bar' );
		$instance->setThreshold( 10 );

		$this->assertIsArray(

			$instance->compareAndFindLabels( $requestOptions )
		);
	}

}
