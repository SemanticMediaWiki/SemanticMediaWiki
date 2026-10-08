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

	public function testChunkedRebuildIndexesValuesOfFixedPropertyTables() {
		[ $sid, $pid ] = $this->storeDisplayTitle( 'ChunkedRebuildFixed', 'deltaword' );
		$this->flushIndex();

		$this->newSearchTableRebuilder()->rebuildChunk( $sid, 1 );

		$this->assertStringContainsString( 'deltaword', $this->readIndexEntry( $sid, $pid ) );
	}

	public function testChunkedRebuildContinuesUpToTheLastSubject() {
		[ $firstSid, $pid ] = $this->storeTexts( 'ChunkedRebuildFirst', 'Has chunked text', [ 'alphaword' ] );
		[ $lastSid ] = $this->storeTexts( 'ChunkedRebuildLast', 'Has chunked text', [ 'bravoword' ] );
		$this->flushIndex();
		$this->assertSame( $lastSid, $this->maxSubjectId(), 'Precondition: the last subject ends the rebuild' );

		$this->newSearchTableRebuilder()->rebuildInChunks( $firstSid, 1 );

		$this->assertStringContainsString( 'alphaword', $this->readIndexEntry( $firstSid, $pid ) );
		$this->assertStringContainsString( 'bravoword', $this->readIndexEntry( $lastSid, $pid ) );
	}

	public function testChunkedRebuildRemovesEntriesOfPropertiesTheSubjectNoLongerHas() {
		[ $sid, $pid ] = $this->storeTexts( 'ChunkedRebuildStale', 'Has chunked text', [ 'alphaword' ] );
		$this->writeIndexEntry( $sid, $pid + 100000, 'staleword' );

		$this->newSearchTableRebuilder()->rebuildInChunks( $sid, 1 );

		$this->assertFalse( $this->readIndexEntry( $sid, $pid + 100000 ) );
	}

	public function testChunkedRebuildRemovesEntriesBeyondTheLastSubject() {
		[ , $pid ] = $this->storeTexts( 'ChunkedRebuildStale', 'Has chunked text', [ 'alphaword' ] );
		$staleSid = $this->maxSubjectId() + 1000;
		$this->writeIndexEntry( $staleSid, $pid, 'staleword' );

		$this->newSearchTableRebuilder()->rebuildInChunks( 0, 500 );

		$this->assertFalse( $this->readIndexEntry( $staleSid, $pid ) );
	}

	private function newSearchTableRebuilder(): SearchTableRebuilder {
		return ( new FulltextSearchTableFactory() )->newSearchTableRebuilder( $this->getStore() );
	}

}
