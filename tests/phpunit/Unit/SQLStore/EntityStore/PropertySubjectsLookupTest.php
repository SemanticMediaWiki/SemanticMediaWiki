<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\DataItem;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\PropertySubjectsLookup;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\EntityStore\PropertySubjectsLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PropertySubjectsLookupTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			PropertySubjectsLookup::class,
			new PropertySubjectsLookup( $store )
		);
	}

	public function testLookupForNonFixedPropertyTable() {
		$dataItem = WikiPage::newFromText( __METHOD__ );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->willReturn( [ 'o_id' => 42 ] );

		$propertyTableDef = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$qb = $this->createMockSelectQueryBuilder( [] );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType', 'getSQLOptions', 'getSQLConditions' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLOptions' )
			->willReturn( [] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLConditions' )
			->willReturn( '' );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$instance = new PropertySubjectsLookup(
			$store
		);

		$instance->fetchFromTable( 42, $propertyTableDef, $dataItem );
	}

	public function testLookupForFixedPropertyTable() {
		$dataItem = WikiPage::newFromText( __METHOD__ );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->atLeastOnce() )
			->method( 'getWhereConds' )
			->willReturn( [ 'o_id' => 42 ] );

		$propertyTableDef = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$qb = $this->createMockSelectQueryBuilder( [] );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType' ] )
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$instance = new PropertySubjectsLookup(
			$store
		);

		$instance->fetchFromTable( 42, $propertyTableDef, $dataItem );
	}

	/**
	 * The `FORCE INDEX(...)` hint for an all-subjects lookup is applied only
	 * when the property's usage exceeds a threshold that scales with the id
	 * table size, with the historical 5000 kept as a floor.
	 *
	 * @dataProvider indexHintProvider
	 */
	public function testIndexHintThresholdScalesWithTableSize( int $usageCount, int $totalEntities, array $expectedUseIndex ) {
		$capturedUseIndex = [];
		$this->runAllSubjectsFetch( $usageCount, $totalEntities, $capturedUseIndex );

		$this->assertSame( $expectedUseIndex, $capturedUseIndex );
	}

	public static function indexHintProvider(): array {
		return [
			// On a large id table a property numerous in absolute terms can
			// still be sparse relative to the table, so the hint is skipped
			// (the regression reported in issue 6559).
			'sparse on large table -> no hint' => [ 6000, 5_000_000, [] ],
			// Dense relative to the same large table -> hint applied.
			'dense on large table -> hint' => [ 20000, 5_000_000, [ [ 't1' => 's_id' ] ] ],
			// On a small table the relative threshold falls below the floor,
			// so the historical behaviour (hint above 5000) is preserved.
			'small table keeps floor -> hint' => [ 6000, 100_000, [ [ 't1' => 's_id' ] ] ],
			// At or below the floor the hint is never applied.
			'below floor -> no hint' => [ 3000, 5_000_000, [] ],
		];
	}

	private function runAllSubjectsFetch( int $usageCount, int $totalEntities, array &$capturedUseIndex ): void {
		$dataItemHandler = $this->getMockForAbstractClass(
			DataItemHandler::class, [], '', false, true, true, [ 'getIndexHint' ]
		);

		$dataItemHandler->method( 'getIndexHint' )
			->willReturn( 's_id' );

		$propertyTableDef = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyTableDef->method( 'usesIdSubject' )->willReturn( true );
		$propertyTableDef->method( 'isFixedPropertyTable' )->willReturn( false );
		$propertyTableDef->method( 'getName' )->willReturn( 'smw_di_blob' );
		$propertyTableDef->method( 'getDiType' )->willReturn( DataItem::TYPE_BLOB );

		// One builder per query the lookup issues, in order: property
		// statistics (usage_count), the id-table size probe (only when the
		// usage clears the floor), and the main subjects query (which receives
		// the index hint, if any).
		$statsBuilder = $this->createMockSelectQueryBuilder( [ (object)[ 'usage_count' => $usageCount ] ] );
		$sizeBuilder = $this->createMockSelectQueryBuilder( [ (object)[ 'm' => $totalEntities ] ] );

		$where = $selects = $tables = [];
		$mainBuilder = $this->createMockSelectQueryBuilder( [], $where, $selects, $tables, $capturedUseIndex );

		$builders = [ $statsBuilder ];
		// Mirrors getIndexHint(): the id-table size probe is issued only after
		// the usage clears the floor (PropertySubjectsLookup::INDEX_HINT_USAGE_FLOOR).
		if ( $usageCount > 5000 ) {
			$builders[] = $sizeBuilder;
		}
		$builders[] = $mainBuilder;

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'newSelectQueryBuilder' )
			->willReturnOnConsecutiveCalls( ...$builders );

		$connection->method( 'expr' )->willReturn( 'expr' );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getConnection', 'getDataItemHandlerForDIType', 'getSQLOptions', 'getSQLConditions' ] )
			->getMock();

		$store->method( 'getConnection' )->willReturn( $connection );
		$store->method( 'getDataItemHandlerForDIType' )->willReturn( $dataItemHandler );
		$store->method( 'getSQLOptions' )->willReturn( [] );
		$store->method( 'getSQLConditions' )->willReturn( '' );

		$instance = new PropertySubjectsLookup( $store );

		$instance->fetchFromTable( 42, $propertyTableDef, null );
	}

}
