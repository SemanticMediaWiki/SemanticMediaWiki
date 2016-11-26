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
class DataRepairSection {

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
	 * @var boolean
	 */
	private $enabledRefreshStore = false;

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
	 * @param boolean $enabledRefreshStore
	 */
	public function enabledRefreshStore( $enabledRefreshStore ) {
		$this->enabledRefreshStore = (bool)$enabledRefreshStore;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getForm() {

		$refreshjob = $this->getRefreshJob();

		$this->htmlFormRenderer
			->setName( 'refreshwiki' )
			->setMethod( 'post' )
			->addHiddenField( 'action', 'refreshstore' )
			->addHeader( 'h2', $this->getMessage( 'smw_smwadmin_datarefresh' ) );

		if ( !$this->enabledRefreshStore ) {
			$this->htmlFormRenderer->addParagraph( $this->getMessage( 'smw-smwadmin-datarefresh-disabled' ) );
		} else {
			$this->htmlFormRenderer->addParagraph( $this->getMessage( 'smw_smwadmin_datarefreshdocu' ) );
		}

		if ( $refreshjob !== null ) {
			$prog = $refreshjob->getProgress();

			$progressBar = Html::rawElement(
				'div',
				array( 'style' => 'float: left; background: #DDDDDD; border: 1px solid grey; width: 300px;' ),
				Html::rawElement( 'div', array( 'style' => 'background: #AAF; width: ' . round( $prog * 300 ) . 'px; height: 20px; ' ), '' )
			);

			$this->htmlFormRenderer
				->addParagraph( $this->getMessage( 'smw_smwadmin_datarefreshprogress' ) )
				->addParagraph( $progressBar . '&#160;' . round( $prog * 100, 4 ) . '%' )
				->addLineBreak();

			if ( $this->enabledRefreshStore ) {
				$this->htmlFormRenderer
					->addSubmitButton( $this->getMessage( 'smw_smwadmin_datarefreshstop' ) )
					->addCheckbox(
						$this->getMessage( 'smw_smwadmin_datarefreshstopconfirm' ),
						'rfsure',
						'stop'
					);
			}

		} elseif ( $this->enabledRefreshStore ) {
			$this->htmlFormRenderer
				->addHiddenField( 'rfsure', 'yes' )
				->addSubmitButton( $this->getMessage( 'smw_smwadmin_datarefreshbutton' ) );
		}

		return $this->htmlFormRenderer->getForm() . Html::element( 'p', array(), '' );
	}

	/**
	 * @since 2.5
	 *
	 * @param WebRequest $webRequest
	 */
	public function doRefresh( WebRequest $webRequest ) {

		$this->outputFormatter->setPageTitle( $this->getMessage( 'smw_smwadmin_datarefresh' ) );
		$this->outputFormatter->addParentLink();

		if ( !$this->enabledRefreshStore ) {
			return $this->outputMessage( 'smw_smwadmin_return' );
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
				$this->outputMessage( 'smw_smwadmin_updatestarted' );
			} else {
				$this->outputMessage( 'smw_smwadmin_updatenotstarted' );
			}

		} elseif ( $sure == 'stop' ) {

			// delete (all) existing iteration jobs
			$this->connection->delete(
				'job',
				array( 'job_cmd' => 'SMW\RefreshJob' ),
				__METHOD__
			);

			$this->outputMessage( 'smw_smwadmin_updatestopped' );
		} else {
			$this->outputMessage( 'smw_smwadmin_updatenotstopped' );
		}
	}

	private function getMessage( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

	private function outputMessage( $message ) {
		$this->outputFormatter->addHTML( '<p>' . $this->getMessage( $message ) . '</p>' );
	}

	private function getRefreshJob() {

		if ( !$this->enabledRefreshStore ) {
			return null;
		}

		if ( $this->refreshjob !== null ) {
			return $this->refreshjob;
		}

		$this->refreshjob = null;

		$jobQueueLookup = ApplicationFactory::getInstance()->create( 'JobQueueLookup', $this->connection );
		$row = $jobQueueLookup->selectJobRowFor( 'SMW\RefreshJob' );

		if ( $row !== null && $row !== false ) { // similar to Job::pop_type, but without deleting the job
			$title = Title::makeTitleSafe( $row->job_namespace, $row->job_title );
			$blob = (string)$row->job_params !== '' ? unserialize( $row->job_params ) : false;
			$this->refreshjob = Job::factory( $row->job_cmd, $title, $blob, $row->job_id );
		}

		return $this->refreshjob;
	}

}
