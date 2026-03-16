<?php

namespace SMW\Tests\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\Property\SpecificationLookup;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   2.5
 *
 * @author mwjames
 */
class PropertyLabelSimilarityLookupTest extends TestCase {

	private $store;
	private $propertyStatisticsStore;
	private $requestOptions;
	private $dataItemFactory;

	protected function setUp(): void {
		$this->dataItemFactory = new DataItemFactory();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( RequestOptions::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyLabelSimilarityLookup::class,
			new PropertyLabelSimilarityLookup( $this->store )
		);
	}

	public function testGetPropertyMaxCount() {
		$this->store->expects( $this->any() )
			->method( 'getStatistics' )
			->willReturn( [ 'TOTALPROPS' => 42 ] );

		$propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
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

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ $row ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
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

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ $row1, $row2 ] );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
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
