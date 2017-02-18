<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
use SMW\Store;
use Html;
use WebRequest;
use Title;
use Job;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class DataRefreshJobTaskHandler extends TaskHandler {

	/**
	 * @var Store
	 */
	private $store;

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
	 * @param Store $store
	 * @param HtmlFormRenderer $htmlFormRenderer
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( Store $store, HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->store = $store;
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
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
			->addHeader( 'h3', $this->getMessageAsString( 'smw_smwadmin_datarefresh' ) )
			->addParagraph( $this->getMessageAsString( 'smw_smwadmin_datarefreshdocu' ) );

		if ( !$this->isEnabledFeature( SMW_ADM_REFRESH ) ) {
			$this->htmlFormRenderer->addParagraph( $this->getMessageAsString( 'smw-admin-feature-disabled' ) );
		} elseif ( $this->getRefreshJob() !== null ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'refreshstore' )
				->addParagraph( $this->getMessageAsString( 'smw_smwadmin_datarefreshprogress' ) )
				->addParagraph( $this->getProgressBar( $this->getRefreshJob()->getProgress() ) )
				->addLineBreak()
				->addSubmitButton(
					$this->getMessageAsString( 'smw_smwadmin_datarefreshstop' ),
					array(
						'class' => ''
					)
				)
				->addCheckbox(
					$this->getMessageAsString( 'smw_smwadmin_datarefreshstopconfirm' ),
					'rfsure',
					'stop'
				);
		} elseif ( $this->getRefreshJob() === null ) {
			$this->htmlFormRenderer
				->setMethod( 'post' )
				->addHiddenField( 'action', 'refreshstore' )
				->addHiddenField( 'rfsure', 'yes' )
				->addSubmitButton(
					$this->getMessageAsString( 'smw_smwadmin_datarefreshbutton' ),
					array(
						'class' => ''
					)
				);
		}

		return Html::rawElement( 'div', array(), $this->htmlFormRenderer->getForm() );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		if ( !$this->isEnabledFeature( SMW_ADM_REFRESH ) ) {
			return $this->outputFormatter->redirectToRootPage();
		}

		$refreshjob = $this->getRefreshJob();
		$sure = $webRequest->getText( 'rfsure' );
		$connection = $this->store->getConnection( 'mw.db' );

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
			$connection->delete(
				'job',
				array( 'job_cmd' => 'SMW\RefreshJob' ),
				__METHOD__
			);
		}

		$this->outputFormatter->redirectToRootPage();
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

		$jobQueueLookup = ApplicationFactory::getInstance()->create(
			'JobQueueLookup',
			$this->store->getConnection( 'mw.db' )
		);

		$row = $jobQueueLookup->selectJobRowBy( 'SMW\RefreshJob' );

		if ( $row !== null && $row !== false ) { // similar to Job::pop_type, but without deleting the job
			$title = Title::makeTitleSafe( $row->job_namespace, $row->job_title );
			$blob = (string)$row->job_params !== '' ? unserialize( $row->job_params ) : false;
			$this->refreshjob = Job::factory( $row->job_cmd, $title, $blob, $row->job_id );
		}

		return $this->refreshjob;
	}

}
