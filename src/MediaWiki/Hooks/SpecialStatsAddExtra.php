<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DataTypeRegistry;
use SMW\Store;

/**
 * Add extra statistic at the end of Special:Statistics
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialStatsAddExtra extends HookHandler {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string[]
	 */
	private $messageMapper = [
		'PROPUSES'    => 'smw-statistics-property-instance',
		'ERRORUSES'   => 'smw-statistics-error-count',
		'TOTALPROPS'  => 'smw-statistics-property-total',
		'USEDPROPS'   => 'smw-statistics-property-used',
		'OWNPAGE'     => 'smw-statistics-property-page',
		'DECLPROPS'   => 'smw-statistics-property-type',
		'DELETECOUNT' => 'smw-statistics-delete-count',
		'SUBOBJECTS'  => 'smw-statistics-subobject-count',
		'QUERY'       => 'smw-statistics-query-inline',
		'CONCEPTS'    => 'smw-statistics-concept-count',
	];

	/**
	 * @since  1.9
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 1.9
	 *
	 * @param array &$extraStats
	 *
	 * @return true
	 */
	public function process( array &$extraStats ) {

		if ( !$this->getOption( 'smwgSemanticsEnabled', false ) ) {
			return true;
		}

		$this->copyStatistics( $extraStats );

		return true;
	}

	private function copyStatistics( &$extraStats ) {

		$statistics = $this->store->getStatistics();

		$extraStats['smw-statistics'] = [];

		foreach ( $this->messageMapper as $key => $message ) {
			if ( isset( $statistics[$key] ) ) {
				$extraStats['smw-statistics'][$message] = $statistics[$key];
			}
		}

		$count = count(
			DataTypeRegistry::getInstance()->getKnownTypeLabels()
		);

		$extraStats['smw-statistics']['smw-statistics-datatype-count'] = $count;
	}

}
