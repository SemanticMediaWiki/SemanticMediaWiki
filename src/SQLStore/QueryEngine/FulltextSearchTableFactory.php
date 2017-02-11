<?php

namespace SMW\SQLStore\QueryEngine;

use SMW\SQLStore\SQLStore;
use SMW\ApplicationFactory;
use SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder;
use SMW\SQLStore\QueryEngine\Fulltext\MySQLValueMatchConditionBuilder;
use SMW\SQLStore\QueryEngine\Fulltext\SQLiteValueMatchConditionBuilder;
use SMW\SQLStore\QueryEngine\Fulltext\TextByChangeUpdater;
use SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use Onoi\Tesa\SanitizerFactory;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableFactory {

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 *
	 * @return ValueMatchConditionBuilder
	 */
	public function newValueMatchConditionBuilderByType( SQLStore $store ) {

		$type = $store->getConnection( 'mw.db' )->getType();

		switch ( $type ) {
			case 'mysql':
				return new MySQLValueMatchConditionBuilder(
					$this->newTextSanitizer(),
					$this->newSearchTable( $store )
				);
				break;
			case 'sqlite':
				return new SQLiteValueMatchConditionBuilder(
					$this->newTextSanitizer(),
					$this->newSearchTable( $store )
				);
				break;
		}

		return new ValueMatchConditionBuilder( $this->newTextSanitizer(), $this->newSearchTable( $store ) );
	}

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 *
	 * @return SearchTable
	 */
	public function newTextSanitizer() {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$textSanitizer = new TextSanitizer(
			new SanitizerFactory()
		);

		$textSanitizer->setLanguageDetection(
			$settings->get( 'smwgFulltextLanguageDetection' )
		);

		$textSanitizer->setMinTokenSize(
			$settings->get( 'smwgFulltextSearchMinTokenSize' )
		);

		return $textSanitizer;
	}

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 *
	 * @return SearchTable
	 */
	public function newSearchTable( SQLStore $store ) {

		$settings = ApplicationFactory::getInstance()->getSettings();

		$searchTable = new SearchTable(
			$store
		);

		$searchTable->setEnabled(
			$settings->get( 'smwgEnabledFulltextSearch' )
		);

		$searchTable->setPropertyExemptionList(
			$settings->get( 'smwgFulltextSearchPropertyExemptionList' )
		);

		$searchTable->setMinTokenSize(
			$settings->get( 'smwgFulltextSearchMinTokenSize' )
		);

		$searchTable->setIndexableDataTypes(
			$settings->get( 'smwgFulltextSearchIndexableDataTypes' )
		);

		return $searchTable;
	}

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 *
	 * @return SearchTableUpdater
	 */
	public function newSearchTableUpdater( SQLStore $store ) {
		return new SearchTableUpdater(
			$store->getConnection( 'mw.db' ),
			$this->newSearchTable( $store ),
			$this->newTextSanitizer()
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 *
	 * @return TextByChangeUpdater
	 */
	public function newTextByChangeUpdater( SQLStore $store ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$textByChangeUpdater = new TextByChangeUpdater(
			$store->getConnection( 'mw.db' ),
			$this->newSearchTableUpdater( $store ),
			$this->newTextSanitizer(),
			$applicationFactory->singleton( 'TempChangeOpStore' )
		);

		$textByChangeUpdater->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$textByChangeUpdater->asDeferredUpdate(
			$settings->get( 'smwgFulltextDeferredUpdate' )
		);

		// https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
		$textByChangeUpdater->isCommandLineMode(
			$GLOBALS['wgCommandLineMode']
		);

		return $textByChangeUpdater;
	}

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 *
	 * @return SearchTableRebuilder
	 */
	public function newSearchTableRebuilder( SQLStore $store ) {
		return new SearchTableRebuilder(
			$store->getConnection( 'mw.db' ),
			$this->newSearchTableUpdater( $store )
		);
	}

}
