<?php

namespace SMW\MediaWiki\Hooks;

use SMW\MediaWiki\JobQueueLookup;

use SMW\Application;
use SMW\DataTypeRegistry;

use Language;

/**
 * Add extra statistic at the end of Special:Statistics
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialStatsAddExtra
 *
 * @ingroup FunctionHook
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SpecialStatsAddExtra {

	/**
	 * @var array
	 */
	protected $extraStats = null;

	/**
	 * @var string
	 */
	protected $version = null;

	/**
	 * @var Language
	 */
	protected $userLanguage;

	/**
	 * @var string[]
	 */
	private $legacyMessageMapper = array(
		'PROPUSES'   => 'smw-statistics-property-instance',
		'USEDPROPS'  => 'smw-statistics-property-total-legacy',
		'OWNPAGE'    => 'smw-statistics-property-page',
		'DECLPROPS'  => 'smw-statistics-property-type',
		'SUBOBJECTS' => 'smw-statistics-subobject-count',
		'QUERY'      => 'smw-statistics-query-inline',
		'CONCEPTS'   => 'smw-statistics-concept-count-legacy'
	);

	/**
	 * @var string[]
	 */
	private $messageMapper = array(
		'PROPUSES'   => 'smw-statistics-property-instance',
		'USEDPROPS'  => 'smw-statistics-property-total',
		'OWNPAGE'    => 'smw-statistics-property-page',
		'DECLPROPS'  => 'smw-statistics-property-type',
		'SUBOBJECTS' => 'smw-statistics-subobject-count',
		'QUERY'      => 'smw-statistics-query-inline',
		'CONCEPTS'   => 'smw-statistics-concept-count'
	);

	/**
	 * @var string[]
	 */
	private $jobQueueMessageMapper = array(
		'UPDATEJOB'   => 'smw-statistics-jobqueue-update-count',
		'REFRESHJOB'  => 'smw-statistics-jobqueue-refresh-count',
		'DELETEJOB'   => 'smw-statistics-jobqueue-delete-count'
	);

	/**
	 * @since  1.9
	 *
	 * @param array &$extraStats
	 * @param string $version
	 * @param Language $userLanguage User language
	 */
	public function __construct( array &$extraStats, $version, Language $userLanguage ) {
		$this->extraStats =& $extraStats;
		$this->version = $version;
		$this->userLanguage = $userLanguage;
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	public function process() {
		return version_compare( $this->version, '1.21', '<' ) ? $this->copyLegacyStatistics() : $this->copyStatistics();
	}

	/**
	 * @since 1.9
	 *
	 * @return true
	 */
	protected function copyStatistics() {

		$statistics = Application::getInstance()->getStore()->getStatistics();

		$this->extraStats['smw-statistics'] = array();

		foreach ( $this->messageMapper as $key => $message ) {

			if ( isset( $statistics[$key] ) ) {
				$this->extraStats['smw-statistics'][ $message ] = $statistics[$key];
			}
		}

		$count = count( DataTypeRegistry::getInstance()->getKnownTypeLabels() );
		$this->extraStats['smw-statistics']['smw-statistics-datatype-count'] = $count;

		if ( Application::getInstance()->getSettings()->get( 'smwgShowJobQueueStatistics' ) ) {
			$this->copyJobQueueStatistics();
		}

		return true;

	}

	/**
	 * Legacy approach to display statistical items for all MW 1.21- versions
	 *
	 * @since 1.9
	 *
	 * @return true
	 */
	protected function copyLegacyStatistics() {

		$statistics = Application::getInstance()->getStore()->getStatistics();

		foreach ( $this->legacyMessageMapper as $key => $message ) {

			if ( isset( $statistics[$key] ) ) {
				$this->extraStats[wfMessage( $message )->text()] = $this->userLanguage->formatNum( $statistics[$key] );
			}
		}

		return true;
	}

	private function copyJobQueueStatistics() {

		$this->extraStats['smw-statistics-jobqueue'] = array();

		$jobQueueLookup = new JobQueueLookup( Application::getInstance()->getStore()->getDatabase() );

		$statistics = $jobQueueLookup->getStatistics();

		foreach ( $this->jobQueueMessageMapper as $key => $message ) {

			if ( isset( $statistics[$key] ) ) {
				$this->extraStats['smw-statistics-jobqueue'][ $message ] = $statistics[$key];
			}
		}
	}

}
