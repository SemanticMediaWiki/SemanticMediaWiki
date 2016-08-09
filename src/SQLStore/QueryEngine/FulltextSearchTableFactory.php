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
	 * @var ApplicationFactory
	 */
	private $applicationFactory;

	/**
	 * @since 2.5
	 */
	public function __construct() {
		$this->applicationFactory = ApplicationFactory::getInstance();
	}

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
					$this->newSearchTable( $store )
				);
				break;
			case 'sqlite':
				return new SQLiteValueMatchConditionBuilder(
					$this->newSearchTable( $store )
				);
				break;
		}

		return new ValueMatchConditionBuilder();
	}

	/**
	 * @since 2.5
	 *
	 * @param SQLStore $store
	 *
	 * @return SearchTable
	 */
	public function newSearchTable( SQLStore $store ) {

		$settings = $this->applicationFactory->getSettings();

		$textSanitizer = new TextSanitizer(
			new SanitizerFactory()
		);

		$textSanitizer->setLanguageDetection(
			$settings->get( 'smwgFulltextLanguageDetection' )
		);

		$textSanitizer->setMinTokenSize(
			$settings->get( 'smwgFulltextSearchMinTokenSize' )
		);

		$searchTable = new SearchTable(
			$store,
			$textSanitizer
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
			$this->newSearchTable( $store ),
			$store->getConnection( 'mw.db' )
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

		$settings = $this->applicationFactory->getSettings();

		$textByChangeUpdater = new TextByChangeUpdater(
			$this->newSearchTableUpdater( $store ),
			$store->getConnection( 'mw.db' )
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
			$this->newSearchTableUpdater( $store ),
			$store->getConnection( 'mw.db' )
		);
	}

}
