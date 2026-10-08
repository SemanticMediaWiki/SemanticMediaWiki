<?php

namespace SMW\Tests\Utils;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataValues\TypesValue;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;
use SMW\SQLStore\SQLStore;

/**
 * Stores text values and reads the full-text index and the queued rebuild
 * chunks, for tests that run against the store of an `SMWIntegrationTestCase`.
 *
 * @license GPL-2.0-or-later
 */
trait FulltextRebuildFixtureTrait {

	/**
	 * @param string $page
	 * @param string $propertyLabel
	 * @param string[] $texts
	 *
	 * @return int[] The subject ID and the property ID
	 */
	private function storeTexts( string $page, string $propertyLabel, array $texts ): array {
		$property = Property::newFromUserLabel( $propertyLabel )->setPropertyValueType( '_txt' );
		$this->storeTextType( $property );

		$subject = WikiPage::newFromText( $page );
		$semanticData = new SemanticData( $subject );

		foreach ( $texts as $text ) {
			$semanticData->addPropertyObjectValue( $property, new Blob( $text ) );
		}

		$this->getStore()->updateData( $semanticData );

		return $this->subjectAndPropertyIds( $subject, $property );
	}

	/**
	 * Display titles are stored in a fixed-property table, which has no `p_id` column.
	 *
	 * @return int[] The subject ID and the property ID
	 */
	private function storeDisplayTitle( string $page, string $text ): array {
		$property = new Property( '_DTITLE' );
		$subject = WikiPage::newFromText( $page );
		$semanticData = new SemanticData( $subject );
		$semanticData->addPropertyObjectValue( $property, new Blob( $text ) );

		$this->getStore()->updateData( $semanticData );

		return $this->subjectAndPropertyIds( $subject, $property );
	}

	/**
	 * @return int[]
	 */
	private function subjectAndPropertyIds( WikiPage $subject, Property $property ): array {
		return [
			$this->getStore()->getObjectIds()->getId( $subject ),
			$this->getStore()->getObjectIds()->getSMWPropertyID( $property )
		];
	}

	/**
	 * Without a stored type the index treats the property as exempted.
	 */
	private function storeTextType( Property $property ): void {
		$semanticData = new SemanticData( $property->getDiWikiPage() );
		$semanticData->addPropertyObjectValue(
			new Property( '_TYPE' ),
			TypesValue::newFromTypeId( '_txt' )->getDataItem()
		);

		$this->getStore()->updateData( $semanticData );
	}

	private function writeIndexEntry( int $sid, int $pid, string $text ): void {
		$searchTableUpdater = $this->newSearchTableUpdater();
		$searchTableUpdater->insert( $sid, $pid );
		$searchTableUpdater->update( $sid, $pid, $text );
	}

	private function readIndexEntry( int $sid, int $pid ): false|string {
		return $this->newSearchTableUpdater()->read( $sid, $pid );
	}

	private function flushIndex(): void {
		$this->newSearchTableUpdater()->flushTable();
	}

	private function maxSubjectId(): int {
		return (int)$this->getStore()->getConnection( 'mw.db' )->newSelectQueryBuilder()
			->select( 'MAX(smw_id)' )
			->from( SQLStore::ID_TABLE )
			->caller( __METHOD__ )
			->fetchField();
	}

	private function clearQueuedChunks(): void {
		$this->getServiceContainer()->getJobQueueGroup()->get( 'smw.fulltextSearchTableRebuild' )->delete();
	}

	/**
	 * @return array[] The `s` and `n` parameters of each queued rebuild job
	 */
	private function queuedChunks(): array {
		$jobQueue = $this->getServiceContainer()->getJobQueueGroup()->get( 'smw.fulltextSearchTableRebuild' );
		$chunks = [];

		foreach ( $jobQueue->getAllQueuedJobs() as $job ) {
			$chunks[] = [ 's' => $job->getParams()['s'], 'n' => $job->getParams()['n'] ];
		}

		return $chunks;
	}

	private function newSearchTableUpdater(): SearchTableUpdater {
		return ( new FulltextSearchTableFactory() )->newSearchTableUpdater( $this->getStore() );
	}

}
