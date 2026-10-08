<?php

namespace SMW\Tests\Utils;

use SMW\DataItems\Blob;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\DataValues\TypesValue;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;
use SMW\SQLStore\QueryEngine\FulltextSearchTableFactory;

/**
 * Stores text values and reads the full-text index, for tests that run
 * against the store of an `SMWIntegrationTestCase`.
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

		return [
			$this->getStore()->getObjectIds()->getSMWPageID( $subject->getDBkey(), $subject->getNamespace(), '', '' ),
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

	private function readIndexEntry( int $sid, int $pid ): false|string {
		return $this->newSearchTableUpdater()->read( $sid, $pid );
	}

	private function flushIndex(): void {
		$this->newSearchTableUpdater()->flushTable();
	}

	private function newSearchTableUpdater(): SearchTableUpdater {
		return ( new FulltextSearchTableFactory() )->newSearchTableUpdater( $this->getStore() );
	}

}
