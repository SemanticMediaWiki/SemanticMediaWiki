<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\Message;
use SMW\MediaWiki\Database;
use Html;
use WebRequest;
use Job;
use Title;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class DataRepairActionHandler {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var integer
	 */
	private $enabledFeatures = 0;

	/**
	 * @var null|Job
	 */
	private $refreshjob = null;

	/**
	 * @since 2.5
	 *
	 * @param Database $connection
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( Database $connection, HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->connection = $connection;
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $feature
	 *
	 * @return boolean
	 */
	public function isEnabledFeature( $feature ) {
		return ( $this->enabledFeatures & $feature ) != 0;
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $enabledFeatures
	 */
	public function setEnabledFeatures( $enabledFeatures ) {
		$this->enabledFeatures = $enabledFeatures;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getForm() {

		$this->htmlFormRenderer
			->setName( 'refreshwiki' )
			->addHeader( 'h2', $this->getMessage( 'smw-smwadmin-refresh-title' ) )
			->addParagraph( $this->getMessage( 'smw-admin-job-scheduler-note', Message::PARSE ) );

		$html = $this->htmlFormRenderer->getForm();

		$this->htmlFormRenderer
			->addHeader( 'h3', $this->getMessage( 'smw_smwadmin_datarefresh' ) )
			->addParagraph( $this->getMessage( 'smw_smwadmin_datarefreshdocu' ) );

		if ( !$this->isEnabledFeature( SMW_ADM_REFRESH ) ) {
			$this->htmlFormRenderer->addParagraph( $this->getMessage( 'smw-admin-feature-disabled' ) );
		} elseif ( $this->getRefreshJob() !== null ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'refreshstore' )
				->addParagraph( $this->getMessage( 'smw_smwadmin_datarefreshprogress' ) )
				->addParagraph( $this->getProgressBar( $this->getRefreshJob()->getProgress() ) )
				->addLineBreak()
				->addSubmitButton(
					$this->getMessage( 'smw_smwadmin_datarefreshstop' ),
					array(
						'class' => ''
					)
				)
				->addCheckbox(
					$this->getMessage( 'smw_smwadmin_datarefreshstopconfirm' ),
					'rfsure',
					'stop'
				);
		} elseif ( $this->getRefreshJob() === null ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'refreshstore' )
				->addHiddenField( 'rfsure', 'yes' )
				->addSubmitButton(
					$this->getMessage( 'smw_smwadmin_datarefreshbutton' ),
					array(
						'class' => ''
					)
				);
		}

		$html .= Html::rawElement( 'div', array(), $this->htmlFormRenderer->getForm() );

		// smw-admin-outdateddisposal
		$this->htmlFormRenderer
				->addHeader( 'h3', $this->getMessage( 'smw-admin-outdateddisposal-title' ) )
				->addParagraph( $this->getMessage( 'smw-admin-outdateddisposal-intro', Message::PARSE ) );

		if ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) && !$this->hasEntityIdDisposerJob() ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'dispose' )
				->addSubmitButton(
					$this->getMessage( 'smw-admin-outdateddisposal-button' ),
					array(
						'class' => ''
					)
				);
		} elseif ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) ) {
			$this->htmlFormRenderer
				->addParagraph(
					Html::element( 'span', array( 'class' => 'smw-admin-circle-orange' ), '' ) .
					Html::element( 'span', array( 'style' => 'font-style:italic; margin-left:25px;' ), $this->getMessage( 'smw-admin-outdateddisposal-active' ) )
				);
		} else {
			$this->htmlFormRenderer
				->addParagraph( $this->getMessage( 'smw-admin-feature-disabled' ) );
		}

		$html .= Html::rawElement( 'div', array(), $this->htmlFormRenderer->getForm() );

		// smw-admin-propertystatistics
		$this->htmlFormRenderer
				->addHeader( 'h3', $this->getMessage( 'smw-admin-propertystatistics-title' ) )
				->addParagraph( $this->getMessage( 'smw-admin-propertystatistics-intro', Message::PARSE ) );

		if ( $this->isEnabledFeature( SMW_ADM_PSTATS ) && !$this->hasPropertyStatisticsRebuildJob() ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'pstatsrebuild' )
				->addSubmitButton(
					$this->getMessage( 'smw-admin-propertystatistics-button' ),
					array(
						'class' => ''
					)
				 );
		} elseif ( $this->isEnabledFeature( SMW_ADM_PSTATS ) ) {
			$this->htmlFormRenderer
				->addParagraph(
					Html::element( 'span', array( 'class' => 'smw-admin-circle-orange' ), '' ) .
					Html::element( 'span', array( 'style' => 'font-style:italic; margin-left:25px;' ), $this->getMessage( 'smw-admin-propertystatistics-active' ) )
				);
		} else {
			$this->htmlFormRenderer
				->addParagraph( $this->getMessage( 'smw-admin-feature-disabled' ) );
		}

		$html .= Html::rawElement( 'div', array(), $this->htmlFormRenderer->getForm() );

		// smw-admin-fulltext
		$this->htmlFormRenderer
				->addHeader( 'h3', $this->getMessage( 'smw-admin-fulltext-title' ) )
				->addParagraph( $this->getMessage( 'smw-admin-fulltext-intro', Message::PARSE ) );

		if ( $this->isEnabledFeature( SMW_ADM_FULLT ) && !$this->hasFulltextSearchTableRebuildJob() ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'fulltrebuild' )
				->addSubmitButton(
					$this->getMessage( 'smw-admin-fulltext-button' ),
					array(
						'class' => ''
					)
				);
		} elseif ( $this->isEnabledFeature( SMW_ADM_FULLT ) ) {
			$this->htmlFormRenderer
				->addParagraph(
					Html::element( 'span', array( 'class' => 'smw-admin-circle-orange' ), '' ) .
					Html::element( 'span', array( 'style' => 'font-style:italic; margin-left:25px;' ), $this->getMessage( 'smw-admin-fulltext-active' ) )
				);
		} else {
			$this->htmlFormRenderer
				->addParagraph( $this->getMessage( 'smw-admin-feature-disabled' ) );
		}

		$html .= Html::rawElement( 'div', array(), $this->htmlFormRenderer->getForm() );

		return Html::rawElement( 'div', array(), $html );
	}

	/**
	 * @since 2.5
	 *
	 * @param WebRequest $webRequest
	 */
	public function doRefresh( WebRequest $webRequest ) {

		if ( !$this->isEnabledFeature( SMW_ADM_REFRESH ) ) {
			return $this->outputFormatter->redirectToRootPage();
		}

		$refreshjob = $this->getRefreshJob();
		$sure = $webRequest->getText( 'rfsure' );

		if ( $sure == 'yes' ) {

			if ( $refreshjob === null ) { // careful, there might be race conditions here

				$newjob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
					'SMW\RefreshJob',
					\SpecialPage::getTitleFor( 'SMWAdmin' ),
					array( 'spos' => 1, 'prog' => 0, 'rc' => 2 )
				);

				$newjob->insert();
			}

		} elseif ( $sure == 'stop' ) {

			// delete (all) existing iteration jobs
			$this->connection->delete(
				'job',
				array( 'job_cmd' => 'SMW\RefreshJob' ),
				__METHOD__
			);
		}

		$this->outputFormatter->redirectToRootPage();
	}

	/**
	 * @since 2.5
	 */
	public function doDispose() {

		if ( $this->isEnabledFeature( SMW_ADM_DISPOSAL ) && !$this->hasEntityIdDisposerJob() ) {
			$entityIdDisposerJob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
				'SMW\EntityIdDisposerJob',
				\SpecialPage::getTitleFor( 'SMWAdmin' )
			);

			$entityIdDisposerJob->insert();
		}

		$this->outputFormatter->redirectToRootPage( $this->getMessage( 'smw-admin-outdateddisposal-title' ) );
	}

	/**
	 * @since 2.5
	 */
	public function doPropertyStatsRebuild() {

		if ( $this->isEnabledFeature( SMW_ADM_PSTATS ) && !$this->hasPropertyStatisticsRebuildJob() ) {
			$propertyStatisticsRebuildJob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
				'SMW\PropertyStatisticsRebuildJob',
				\SpecialPage::getTitleFor( 'SMWAdmin' )
			);

			$propertyStatisticsRebuildJob->insert();
		}

		$this->outputFormatter->redirectToRootPage( $this->getMessage( 'smw-admin-propertystatistics-title' ) );
	}

	/**
	 * @since 2.5
	 */
	public function doFulltextSearchTableRebuild() {

		if ( $this->isEnabledFeature( SMW_ADM_FULLT ) && !$this->hasFulltextSearchTableRebuildJob() ) {
			$fulltextSearchTableRebuildJob = ApplicationFactory::getInstance()->newJobFactory()->newByType(
				'SMW\FulltextSearchTableRebuildJob',
				\SpecialPage::getTitleFor( 'SMWAdmin' )
			);

			$fulltextSearchTableRebuildJob->insert();
		}

		$this->outputFormatter->redirectToRootPage( $this->getMessage( 'smw-admin-fulltext-title' ) );
	}

	private function getMessage( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

	private function outputMessage( $message ) {
		$this->outputFormatter->addHTML( '<p>' . $this->getMessage( $message ) . '</p>' );
	}

	private function getProgressBar( $prog ) {
		return Html::rawElement(
			'div',
			array( 'style' => 'float: left; background: #DDDDDD; border: 1px solid grey; width: 300px;' ),
			Html::rawElement( 'div', array( 'style' => 'background: #AAF; width: ' . round( $prog * 300 ) . 'px; height: 20px; ' ), '' )
		) . '&#160;' . round( $prog * 100, 4 ) . '%';
	}

	private function getRefreshJob() {

		if ( !$this->isEnabledFeature( SMW_ADM_REFRESH ) ) {
			return null;
		}

		if ( $this->refreshjob !== null ) {
			return $this->refreshjob;
		}

		$this->refreshjob = null;

		$jobQueueLookup = ApplicationFactory::getInstance()->create( 'JobQueueLookup', $this->connection );
		$row = $jobQueueLookup->selectJobRowBy( 'SMW\RefreshJob' );

		if ( $row !== null && $row !== false ) { // similar to Job::pop_type, but without deleting the job
			$title = Title::makeTitleSafe( $row->job_namespace, $row->job_title );
			$blob = (string)$row->job_params !== '' ? unserialize( $row->job_params ) : false;
			$this->refreshjob = Job::factory( $row->job_cmd, $title, $blob, $row->job_id );
		}

		return $this->refreshjob;
	}

	private function hasEntityIdDisposerJob() {

		if ( !$this->isEnabledFeature( SMW_ADM_DISPOSAL ) ) {
			return false;
		}

		$jobQueueLookup = ApplicationFactory::getInstance()->create( 'JobQueueLookup', $this->connection );

		$row = $jobQueueLookup->selectJobRowBy(
			'SMW\EntityIdDisposerJob'
		);

		return $row !== null && $row !== false;
	}

	private function hasPropertyStatisticsRebuildJob() {

		if ( !$this->isEnabledFeature( SMW_ADM_PSTATS ) ) {
			return false;
		}

		$jobQueueLookup = ApplicationFactory::getInstance()->create( 'JobQueueLookup', $this->connection );

		$row = $jobQueueLookup->selectJobRowBy(
			'SMW\PropertyStatisticsRebuildJob'
		);

		return $row !== null && $row !== false;
	}

	private function hasFulltextSearchTableRebuildJob() {

		if ( !$this->isEnabledFeature( SMW_ADM_FULLT ) ) {
			return false;
		}

		$jobQueueLookup = ApplicationFactory::getInstance()->create( 'JobQueueLookup', $this->connection );

		$row = $jobQueueLookup->selectJobRowBy(
			'SMW\FulltextSearchTableRebuildJob'
		);

		return $row !== null && $row !== false;
	}

}
