<?php

namespace SMW\Tests\Integration\SQLStore\QueryEngine\Fulltext;

use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\Tests\SMWIntegrationTestCase;
use SMW\Tests\Utils\FulltextRebuildFixtureTrait;

/**
 * Runs chunked rebuilds against the database, because the chunk bounds are
 * applied in SQL to the property tables and to the index table.
 *
 * @covers \SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class SearchTableRebuilderTest extends SMWIntegrationTestCase {

	use FulltextRebuildFixtureTrait;

	public function testChunkedRebuildIndexesEveryValueOfAMultiValuedProperty() {
		[ $sid, $pid ] = $this->storeTexts( 'ChunkedRebuildMultiValue', 'Has chunked text', [ 'alphaword', 'bravoword', 'charlieword' ] );
		$this->flushIndex();

		$this->newSearchTableRebuilder()->rebuildChunk( $sid, 1 );

		$indexEntry = $this->readIndexEntry( $sid, $pid );
		$this->assertStringContainsString( 'alphaword', $indexEntry );
		$this->assertStringContainsString( 'bravoword', $indexEntry );
		$this->assertStringContainsString( 'charlieword', $indexEntry );
	}

	private function newSearchTableRebuilder(): SearchTableRebuilder {
		return ( new FulltextSearchTableFactory() )->newSearchTableRebuilder( $this->getStore() );
	}

}
