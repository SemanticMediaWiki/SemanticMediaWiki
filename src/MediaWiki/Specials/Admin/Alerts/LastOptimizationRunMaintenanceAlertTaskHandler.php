<?php

namespace SMW\MediaWiki\Specials\Admin\Alerts;

use Html;
use DateTime;
use SMW\Message;
use SMW\SetupFile;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;

/**
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class LastOptimizationRunMaintenanceAlertTaskHandler extends TaskHandler {

	/**
	 * Defines the threshold in days, exceeding the threholds will trigger the
	 * alert.
	 */
	const DAYS_THRESHOLD = 90; // 3 Month;

	/**
	 * @var SetupFile
	 */
	private $setupFile;

	/**
	 * @since 3.2
	 *
	 * @param SetupFile $setupFile
	 */
	public function __construct( SetupFile $setupFile ) {
		$this->setupFile = $setupFile;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() : string {

		if ( !$this->hasFeature( SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN ) ) {
			return '';
		}

		$lastRun = $this->setupFile->get( SetupFile::LAST_OPTIMIZATION_RUN );

		if ( $lastRun === null ) {
			return '';
		}

		$dateTime = new DateTime( $lastRun );
		$daysDiff = (int)$dateTime->diff( new DateTime( 'now' ) )->format( '%R%a' );

		return $this->buildHTML( $lastRun, $daysDiff );
	}

	private function buildHTML( $lastRun, $daysDiff ) {

		if ( $daysDiff < self::DAYS_THRESHOLD ) {
			return '';
		}

		$html = Html::rawElement(
			'fieldset',
			[
				'class' => "smw-admin-alerts-section-legend-info"
			],
			Html::rawElement(
				'legend',
				[
					'class' => "smw-admin-alerts-section-legend-info"
				],
				$this->msg( "smw-admin-maintenancealerts-lastoptimizationrun-alert-title" )
			) .	Html::rawElement(
				'p',
				[],
				$this->msg( [ 'smw-admin-maintenancealerts-lastoptimizationrun-alert', $lastRun, $daysDiff, self::DAYS_THRESHOLD ], Message::PARSE )
			)
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-admin-alerts smw-admin-alerts-last-optimization-run'
			],
			$html
		);
	}

}
