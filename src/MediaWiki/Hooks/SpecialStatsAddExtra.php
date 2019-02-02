<?php

namespace SMW\MediaWiki\Hooks;

use SMW\DataTypeRegistry;
use SMW\Store;
use SMW\Message;

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
	 * Specifies the point where outdated entities should be removed
	 * instead of accumulating in the DB.
	 */
	const CRITICAL_DELETECOUNT = 5000;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var Language|string
	 */
	private $language;

	/**
	 * @var []
	 */
	private $dataTypeLabels = [];

	/**
	 * @var string[]
	 */
	private $messageMapper = [
		'PROPUSES'      => 'smw-statistics-property-instance',
		'ERRORUSES'     => 'smw-statistics-error-count',
		'TOTALPROPS'    => 'smw-statistics-property-total',
		'USEDPROPS'     => 'smw-statistics-property-used',
		'OWNPAGE'       => 'smw-statistics-property-page',
		'DECLPROPS'     => 'smw-statistics-property-type',
		'TOTALENTITIES' => 'smw-statistics-entities-total',
		'DELETECOUNT'   => 'smw-statistics-delete-count',
		'SUBOBJECTS'    => 'smw-statistics-subobject-count',
		'CONCEPTS'      => 'smw-statistics-concept-count',
		'QUERY'         => 'smw-statistics-query-inline',
		'DATATYPECOUNT' => 'smw-statistics-datatype-count'
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
	 * @since 3.1
	 *
	 * @param Language|string $language
	 */
	public function setLanguage( $language ) {
		$this->language = $language;
	}

	/**
	 * @since 3.1
	 *
	 * @param array
	 */
	public function setDataTypeLabels( $dataTypeLabels ) {
		$this->dataTypeLabels = $dataTypeLabels;
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
		$statistics['DATATYPECOUNT'] = count( $this->dataTypeLabels );

		if ( isset( $statistics['_cache'] ) ) {
			$header = 'smw-statistics-cached';
		} else {
			$header = 'smw-statistics';
		}

		foreach ( $this->messageMapper as $key => $msgKey ) {

			if ( !isset( $statistics[$key] ) ) {
				continue;
			}

			$count = $statistics[$key];
			$message = $this->msg( $msgKey );

			if ( ( $info = $this->msg( $msgKey . '-info' ) ) !== '' && $this->getOption( 'no.tooltip', false ) === false ) {
				$message .= "&nbsp;<span class='smw-highlighter' data-content='{$info}'>ⁱ</span>";
			}

			if ( $key === 'DELETECOUNT' && $count > self::CRITICAL_DELETECOUNT ) {
				$message = "<span style='color:red;'>⚠</span>&nbsp;{$message}";
			}

			if ( $key === 'USEDPROPS' || $key === 'OWNPAGE' || $key === 'DECLPROPS' || $key === 'ERRORUSES' ) {
				$message = '&nbsp;&nbsp;-&nbsp;&nbsp;' . $message;
			}

			if ( $key === 'DELETECOUNT' || $key === 'SUBOBJECTS' || $key === 'QUERY' || $key === 'CONCEPTS' ) {
				$message = "<span class='plainlinks'>&nbsp;&nbsp;-&nbsp;&nbsp;$message</span>";
			}

			$extraStats[$header][] = [
				'name'   => $message,
				'number' => $count
			];

			if ( $key === 'QUERY' ) {
				$extraStats[$header] += $this->addFormats(
					count( $extraStats[$header] ),
					$statistics
				);
			}
		}
	}

	private function addFormats( $key, $statistics ) {

		$i = 0;
		$formats = [];

		foreach ( $statistics['QUERYFORMATS'] as $k => $v ) {

			// Add only the top 3 + count/debug
			if ( $v > 0 && ( $k === 'count' || $k === 'debug' || $i < 3 ) ) {
				$message = '&nbsp;&nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;' . $this->msg( [ 'smw-statistics-query-format', $k ] );

				$formats[$key++] = [
					'name'   => $message,
					'number' => $v
				];
			}

			$i++;
		}

		return $formats;
	}

	private function msg( $args ) {

		if ( $this->getOption( 'plain.msg_key', false ) ) {
			return is_array( $args ) ? implode( '.', $args ) : $args;
		}

		if ( Message::exists( $args ) ) {
			return Message::get( $args, Message::PARSE, $this->language );
		}

		return '';
	}

}
