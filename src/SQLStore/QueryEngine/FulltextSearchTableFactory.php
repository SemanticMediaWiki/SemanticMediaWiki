<?php

namespace SMW\SQLStore\QueryEngine;

use Onoi\Tesa\SanitizerFactory;
use SMW\ApplicationFactory;
use SMW\SQLStore\QueryEngine\Fulltext\MySQLValueMatchConditionBuilder;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTable;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableRebuilder;
use SMW\SQLStore\QueryEngine\Fulltext\SearchTableUpdater;
use SMW\SQLStore\QueryEngine\Fulltext\SQLiteValueMatchConditionBuilder;
use SMW\SQLStore\QueryEngine\Fulltext\TextChangeUpdater;
use SMW\SQLStore\QueryEngine\Fulltext\TextSanitizer;
use SMW\SQLStore\QueryEngine\Fulltext\ValueMatchConditionBuilder;
use SMW\Store;

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
	 * @param Store $store
	 *
	 * @return ValueMatchConditionBuilder
	 */
	public function newValueMatchConditionBuilderByType( Store $store ) {

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
	 * @param Store $store
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
	 * @param Store $store
	 *
	 * @return SearchTable
	 */
	public function newSearchTable( Store $store ) {

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
	 * @param Store $store
	 *
	 * @return SearchTableUpdater
	 */
	public function newSearchTableUpdater( Store $store ) {
		return new SearchTableUpdater(
			$store->getConnection( 'mw.db' ),
			$this->newSearchTable( $store ),
			$this->newTextSanitizer()
		);
	}

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 *
	 * @return TextChangeUpdater
	 */
	public function newTextChangeUpdater( Store $store ) {

		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$textChangeUpdater = new TextChangeUpdater(
			$store->getConnection( 'mw.db' ),
			$applicationFactory->getCache(),
			$this->newSearchTableUpdater( $store )
		);

		$textChangeUpdater->setLogger(
			$applicationFactory->getMediaWikiLogger()
		);

		$textChangeUpdater->asDeferredUpdate(
			$settings->get( 'smwgFulltextDeferredUpdate' )
		);

		// https://www.mediawiki.org/wiki/Manual:$wgCommandLineMode
		$textChangeUpdater->isCommandLineMode(
			$GLOBALS['wgCommandLineMode']
		);

		return $textChangeUpdater;
	}

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 *
	 * @return SearchTableRebuilder
	 */
	public function newSearchTableRebuilder( Store $store ) {
		return new SearchTableRebuilder(
			$store->getConnection( 'mw.db' ),
			$this->newSearchTableUpdater( $store )
		);
	}

}
