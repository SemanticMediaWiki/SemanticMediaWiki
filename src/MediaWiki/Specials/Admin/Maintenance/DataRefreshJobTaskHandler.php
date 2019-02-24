<?php

namespace SMW\MediaWiki\Specials\Admin\Maintenance;

use Html;
use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use Title;
use WebRequest;
use SMW\MediaWiki\Job;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class DataRefreshJobTaskHandler extends TaskHandler {

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var null|Job
	 */
	private $refreshjob = null;

	/**
	 * @since 2.5
	 *
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_MAINTENANCE;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return true;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === 'refreshstore';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$this->htmlFormRenderer
			->addHeader( 'h4', $this->msg( 'smw_smwadmin_datarefresh' ) )
			->addParagraph( $this->msg( 'smw_smwadmin_datarefreshdocu' ) );

		if ( !$this->isEnabledFeature( SMW_ADM_REFRESH ) ) {
			$this->htmlFormRenderer->addParagraph( $this->msg( 'smw-admin-feature-disabled' ) );
		} elseif ( $this->getRefreshJob() !== null ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'refreshstore' )
				->addParagraph( $this->msg( 'smw_smwadmin_datarefreshprogress' ) )
				->addParagraph( $this->getProgressBar( $this->getRefreshJob()->getProgress() ) )
				->addLineBreak()
				->addSubmitButton(
					$this->msg( 'smw_smwadmin_datarefreshstop' ),
					[
						'class' => ''
					]
				)
				->addCheckbox(
					$this->msg( 'smw_smwadmin_datarefreshstopconfirm' ),
					'rfsure',
					'stop'
				);
		} elseif ( $this->getRefreshJob() === null ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'refreshstore' )
				->addHiddenField( 'rfsure', 'yes' )
				->addSubmitButton(
					$this->msg( 'smw_smwadmin_datarefreshbutton' ),
					[
						'class' => ''
					]
				);
		}

		return Html::rawElement( 'div', [], $this->htmlFormRenderer->getForm() );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		if ( !$this->isEnabledFeature( SMW_ADM_REFRESH ) ) {
			return '';
		}

		$sure = $webRequest->getText( 'rfsure' );
		$applicationFactory = ApplicationFactory::getInstance();

		if ( $sure == 'yes' ) {
			$refreshjob = $this->getRefreshJob();

			if ( $refreshjob === null ) { // careful, there might be race conditions here

				$newjob = $applicationFactory->newJobFactory()->newByType(
					'SMW\RefreshJob',
					\SpecialPage::getTitleFor( 'SMWAdmin' ),
					[ 'spos' => 1, 'prog' => 0, 'rc' => 2 ]
				);

				$newjob->insert();
			}

		} elseif ( $sure == 'stop' ) {
			$jobQueue = $applicationFactory->getJobQueue();
			$jobQueue->disableCache();
			$jobQueue->delete( 'SMW\RefreshJob' );
		}

		$this->outputFormatter->redirectToRootPage( '', [ 'tab' => 'maintenance' ] );
	}

	private function getProgressBar( $prog ) {
		return Html::rawElement(
			'div',
			[ 'style' => 'float: left; background: #DDDDDD; border: 1px solid grey; width: 300px;' ],
			Html::rawElement( 'div', [ 'style' => 'background: #AAF; width: ' . round( $prog * 300 ) . 'px; height: 20px; ' ], '' )
		) . '&#160;' . round( $prog * 100, 4 ) . '%';
	}

	private function getRefreshJob() {

		if ( !$this->isEnabledFeature( SMW_ADM_REFRESH ) ) {
			return null;
		}

		if ( $this->refreshjob !== null ) {
			return $this->refreshjob;
		}

		$jobQueue = ApplicationFactory::getInstance()->getJobQueue();

		if ( !$jobQueue->hasPendingJob( 'SMW\RefreshJob' ) ) {
			return null;
		}

		// Pop and acknowledge the job to fetch progress details
		// from itself
		$refreshJob = $jobQueue->pop( 'SMW\RefreshJob' );

		if ( $refreshJob instanceof Job ) {
			$refreshJob->run();
			$jobQueue->ack( $refreshJob );
			$this->refreshjob = $refreshJob;
		}

		return $this->refreshjob;
	}

}
