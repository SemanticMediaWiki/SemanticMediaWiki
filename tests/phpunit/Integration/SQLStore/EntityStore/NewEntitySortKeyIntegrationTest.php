<?php

namespace SMW\Tests\Integration\SQLStore\EntityStore;

use SMW\SQLStore\SQLStore;
use SMW\Tests\SMWIntegrationTestCase;
use stdClass;

/**
 * Needs the database: the byte limit of the ID table's sort columns is the behaviour under test.
 *
 * @group semantic-mediawiki
 * @group Database
 * @group medium
 *
 * @covers \SMW\SQLStore\EntityStore\EntityIdManager
 * @covers \SMW\SQLStore\TableFieldUpdater
 *
 * @license GPL-2.0-or-later
 * @since 7.4.1
 */
class NewEntitySortKeyIntegrationTest extends SMWIntegrationTestCase {

	public function testCutsAMultibyteSortKeyOver255BytesAtACharacterBoundary() {
		$row = $this->storeEntity( 'Long_multibyte_sort_key', str_repeat( 'Ж', 128 ) );

		$this->assertSame( str_repeat( 'Ж', 127 ), $row->smw_sortkey );
	}

	public function testStoresANewPageWithALongTitleUnderAUcaCollation() {
		$this->testEnvironment->addConfiguration( 'smwgEntityCollation', 'uca-default' );

		$id = $this->getStore()->getObjectIds()->makeSMWPageID( rtrim( str_repeat( 'Long_title_', 18 ), '_' ), NS_MAIN, '', '', true, '' );

		$this->assertGreaterThan( 0, $id );
	}

	public function testKeepsTheSortFieldsWhenAPageWithALongSortKeyIsStoredAgain() {
		$sortKey = str_repeat( 'Ж', 128 );
		$first = $this->storeEntity( 'Stored_twice', $sortKey );

		$second = $this->storeEntity( 'Stored_twice', $sortKey );

		$this->assertEquals( $first, $second );
	}

	private function storeEntity( string $title, string $sortKey ): stdClass {
		$store = $this->getStore();
		$id = $store->getObjectIds()->makeSMWPageID( $title, NS_MAIN, '', '', true, $sortKey );

		return $store->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( [ 'smw_sortkey', 'smw_sort' ] )
			->from( SQLStore::ID_TABLE )
			->where( [ 'smw_id' => $id ] )
			->caller( __METHOD__ )
			->fetchRow();
	}

}
