<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\MediaWiki\Connection\Database;
use SMW\Property\SpecificationLookup;
use SMW\RequestOptions;
use SMW\SQLStore\Lookup\PropertyLabelSimilarityLookup;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

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

	use MockSelectQueryBuilderTrait;

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
		$row = new stdClass;
		$row->smw_title = 'Foo';

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [ $row ] ) );

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

	public function testZeroLimitAppliesBoundedPropertyPoolLimit() {
		// A requested limit of 0 (or negative) must not skip the SQL LIMIT and
		// pull the whole property table into the O(n^2) label comparison; the
		// property pool is capped to a bounded maximum instead.
		$row = new stdClass;
		$row->smw_title = 'Foo';

		$capturedLimits = [];
		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$capturedUseIndex = [];

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder(
				[ $row ],
				$whereConditions,
				$capturedSelects,
				$capturedTables,
				$capturedUseIndex,
				$capturedLimits
			) );

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
		$requestOptions->setLimit( 0 );

		$instance->compareAndFindLabels( $requestOptions );

		$this->assertCount(
			1,
			$capturedLimits,
			'a bounded SQL LIMIT must be applied when the requested limit is 0'
		);
		$this->assertGreaterThan( 0, $capturedLimits[0] );
		$this->assertLessThanOrEqual( 100, $capturedLimits[0] );
	}

	public function testCompareAndFindLabelsWithExemption() {
		$row1 = new stdClass;
		$row1->smw_title = 'Foo';

		$row2 = new stdClass;
		$row2->smw_title = 'Foobar';

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $this->createMockSelectQueryBuilder( [ $row1, $row2 ] ) );

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
