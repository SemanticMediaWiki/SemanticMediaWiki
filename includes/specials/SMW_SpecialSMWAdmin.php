<?php

use SMW\ApplicationFactory;
use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\MediaWiki\ManualEntryLogger;
use SMW\Settings;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\Store;
use SMW\StoreFactory;

/**
 * @defgroup SMWSpecialPage
 * This group contains all parts of SMW that are maintenance scripts.
 * @ingroup SMW
 */

/**
 * @author Markus Krötzsch
 *
 * This special page for MediaWiki provides an administrative interface
 * that allows to execute certain functions related to the maintainance
 * of the semantic database. It is restricted to users with siteadmin status.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
class SMWAdmin extends SpecialPage {

	protected $store = null;

	/**
	 * @var MessageBuilder
	 */
	private $messageBuilder;

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	public function __construct() {
		parent::__construct( 'SMWAdmin', 'smw-admin' );
		$this->store = StoreFactory::getStore();
	}

	public function doesWrites() {
		return true;
	}

	public function setStore( Store $store ) {
		$this->store = $store;
	}

	public function getStore() {
		return $this->store;
	}

	public function execute( $par ) {

		if ( !$this->userCanExecute( $this->getUser() ) ) {
			// If the user is not authorized, show an error.
			$this->displayRestrictionError();
			return;
		}

		$this->setHeaders();

		$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();

		$this->htmlFormRenderer = $mwCollaboratorFactory->newHtmlFormRenderer(
			$this->getContext()->getTitle(),
			$this->getLanguage()
		);

		$this->messageBuilder = $this->htmlFormRenderer->getMessageBuilder();

		$jobQueueLookup = $mwCollaboratorFactory->newJobQueueLookup( $this->getStore()->getConnection( 'mw.db' ) );
		$row = $jobQueueLookup->selectJobRowFor( 'SMW\RefreshJob' );

		if ( $row !== false ) { // similar to Job::pop_type, but without deleting the job
			$title = Title::makeTitleSafe( $row->job_namespace, $row->job_title );
			$blob = (string)$row->job_params !== '' ? unserialize( $row->job_params ) : false;
			$refreshjob = Job::factory( $row->job_cmd, $title, $blob, $row->job_id );
		} else {
			$refreshjob = null;
		}

		/**** Execute actions if any ****/
		switch ( $this->getRequest()->getText( 'action' ) ) {
			case 'listsettings':
				return $this->doListConfigurationSettings();
			case 'updatetables':
				return $this->doUpdateTables();
			case 'refreshstore':
				return $this->doRefreshStore( $refreshjob );
		}

		/**** Normal output ****/

		$html = $this->htmlFormRenderer
			->setName( 'buildtables' )
			->setMethod( 'post' )
			->addParagraph( $this->messageBuilder->getMessage( 'smw_smwadmin_docu' )->text() )
			->addHiddenField( 'action', 'updatetables' )
			->addHeader( 'h2', $this->messageBuilder->getMessage( 'smw_smwadmin_db' )->text() )
			->addParagraph( $this->messageBuilder->getMessage( 'smw_smwadmin_dbdocu' )->text() )
			->addParagraph( $this->messageBuilder->getMessage( 'smw_smwadmin_permissionswarn' )->text() )
			->addHiddenField( 'udsure', 'yes' )
			->addSubmitButton( $this->messageBuilder->getMessage( 'smw_smwadmin_dbbutton' )->text() )
			->getForm();

		$html .= Html::element( 'p', array(), '' );

		$this->htmlFormRenderer
			->setName( 'refreshwiki' )
			->setMethod( 'post' )
			->addHiddenField( 'action', 'refreshstore' )
			->addHeader( 'h2', $this->messageBuilder->getMessage( 'smw_smwadmin_datarefresh' )->text() )
			->addParagraph( $this->messageBuilder->getMessage( 'smw_smwadmin_datarefreshdocu' )->text() );

		if ( !is_null( $refreshjob ) ) {
			$prog = $refreshjob->getProgress();

			$progressBar = Html::rawElement(
				'div',
				array( 'style' => 'float: left; background: #DDDDDD; border: 1px solid grey; width: 300px;' ),
				Html::rawElement( 'div', array( 'style' => 'background: #AAF; width: ' . round( $prog * 300 ) . 'px; height: 20px; ' ), '' )
			);

			$this->htmlFormRenderer
				->addParagraph( $this->messageBuilder->getMessage( 'smw_smwadmin_datarefreshprogress' )->text() )
				->addParagraph( $progressBar . '&#160;' . round( $prog * 100, 4 ) . '%' )
				->addLineBreak();

			if ( $GLOBALS['smwgAdminRefreshStore'] ) {

				$this->htmlFormRenderer
					->addSubmitButton( $this->messageBuilder->getMessage( 'smw_smwadmin_datarefreshstop' )->text() )
					->addCheckbox(
						$this->messageBuilder->getMessage( 'smw_smwadmin_datarefreshstopconfirm' )->escaped(),
						'rfsure',
						'stop' );
			}

		} elseif ( $GLOBALS['smwgAdminRefreshStore'] ) {

			$this->htmlFormRenderer
				->addHiddenField( 'rfsure', 'yes' )
				->addSubmitButton( $this->messageBuilder->getMessage( 'smw_smwadmin_datarefreshbutton' )->text() );
		}

		if ( $this->getRequest()->getText( 'action' ) === 'iddispose' ) {
			$this->doIdDispose( (int)$this->getRequest()->getVal( 'id' ) );
		}

		$id = (int)$this->getRequest()->getText( 'id' );

		$html .= $this->htmlFormRenderer->getForm() . Html::element( 'p', array(), '' );
		$html .= $this->getSettingsSectionForm() . Html::element( 'p', array(), '' );
		$html .= $this->getIdLookupSectionForm( $id ) . Html::element( 'p', array(), '' );
		$html .= $this->getIdDisposeSectionForm( $id ) . Html::element( 'p', array(), '' );
		$html .= $this->getAnnounceSectionForm() . Html::element( 'p', array(), '' );
		$html .= $this->getSupportSectionForm();

		$this->getOutput()->addHTML( $html );
	}

	protected function getSettingsSectionForm() {
		return $this->htmlFormRenderer
			->setName( 'listsettings' )
			->setMethod( 'post' )
			->addHiddenField( 'action', 'listsettings' )
			->addHeader( 'h2', $this->messageBuilder->getMessage( 'smw-sp-admin-settings-title' )->text() )
			->addParagraph( $this->messageBuilder->getMessage( 'smw-sp-admin-settings-docu' )->parse() )
			->addSubmitButton( $this->messageBuilder->getMessage( 'smw-sp-admin-settings-button' )->text() )
			->getForm();
	}

	protected function getAnnounceSectionForm() {
		return $this->htmlFormRenderer
			->setName( 'announce' )
			->setMethod( 'get' )
			->setActionUrl( 'https://wikiapiary.com/wiki/WikiApiary:Semantic_MediaWiki_Registry' )
			->addHeader( 'h2', $this->messageBuilder->getMessage('smw_smwadmin_announce' )->text() )
			->addParagraph( $this->messageBuilder->getMessage( 'smw_smwadmin_announce_text' )->text() )
			->addSubmitButton( $this->messageBuilder->getMessage( 'smw_smwadmin_announce' )->text() )
			->getForm();
	}

	protected function getSupportSectionForm() {
		return $this->htmlFormRenderer
			->setName( 'support' )
			->addHeader( 'h2', $this->messageBuilder->getMessage('smw_smwadmin_support' )->text() )
			->addParagraph( $this->messageBuilder->getMessage( 'smw_smwadmin_supportdocu' )->text() )
			->addParagraph(
				Html::rawElement( 'ul', array(),
					Html::rawElement( 'li', array(), $this->messageBuilder->getMessage( 'smw_smwadmin_installfile' )->text() ) .
					Html::rawElement( 'li', array(), $this->messageBuilder->getMessage( 'smw_smwadmin_smwhomepage' )->text() ) .
					Html::rawElement( 'li', array(), $this->messageBuilder->getMessage( 'smw_smwadmin_mediazilla' )->text() ) .
					Html::rawElement( 'li', array(), $this->messageBuilder->getMessage( 'smw_smwadmin_questions' )->text() )
				) )
			->getForm();
	}

	protected function getIdLookupSectionForm( $id ) {

		$message = '';

		if ( $id > 0 && $this->getRequest()->getText( 'action' ) === 'idlookup' ) {

			$row = $this->getStore()->getConnection( 'mw.db' )->selectRow(
					\SMWSql3SmwIds::TABLE_NAME,
					array(
						'smw_title',
						'smw_namespace',
						'smw_iw',
						'smw_subobject',
						'smw_sortkey'
					),
					'smw_id=' . $id,
					__METHOD__
			);

			$message = '<pre>' . $this->encodeJson( array( $id, $row ) ) . '</pre>';
		} else {
			$id = null;
		}

		return $this->htmlFormRenderer
			->setName( 'idlookup' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'idlookup' )
			->addHeader( 'h2', $this->messageBuilder->getMessage( 'smw-sp-admin-idlookup-title' )->text() )
			->addParagraph( $this->messageBuilder->getMessage( 'smw-sp-admin-idlookup-docu' )->text() )
			->addInputField(
				$this->messageBuilder->getMessage( 'smw-sp-admin-objectid' )->text(),
				'id',
				$id
			)
			->addNonBreakingSpace()
			->addSubmitButton( $this->messageBuilder->getMessage( 'allpagessubmit' )->text() )
			->addParagraph( $message )
			->getForm();
	}

	protected function getIdDisposeSectionForm( $id ) {

		$message = '';

		if ( $id < 1 ) {
			$id = null;
		}

		if ( $id > 0 && $GLOBALS['wgRequest']->getText( 'dispose' ) == 'yes' ) {
			$message = $this->messageBuilder->getMessage( 'smw-sp-admin-iddispose-done', $id )->text();
			$id = null;
		}

		return $this->htmlFormRenderer
			->setName( 'iddispose' )
			->setMethod( 'get' )
			->addHiddenField( 'action', 'iddispose' )
			->addHeader( 'h2', $this->messageBuilder->getMessage( 'smw-sp-admin-iddispose-title' )->text() )
			->addParagraph( $this->messageBuilder->getMessage( 'smw-sp-admin-iddispose-docu' )->parse() )
			->addParagraph( $message )
			->addHiddenField( 'id', $id )
			->addInputField(
				$this->messageBuilder->getMessage( 'smw-sp-admin-objectid' )->text(),
				'id',
				$id,
				null,
				20,
				'',
				true
			)
			->addNonBreakingSpace()
			->addSubmitButton( $this->messageBuilder->getMessage( 'allpagessubmit' )->text() )
			->addCheckbox(
				$this->messageBuilder->getMessage( 'smw_smwadmin_datarefreshstopconfirm' )->escaped(),
				'dispose',
				'yes'
			)
			->getForm();
	}

	protected function doUpdateTables() {
		if ( $GLOBALS['wgRequest']->getText( 'udsure' ) == 'yes' ) {

			$this->printRawOutput( function() {
				$result = SMWStore::setupStore();
				if ( $result === true ) {
					print '<p><b>' . wfMessage( 'smw_smwadmin_setupsuccess' )->text() . "</b></p>\n";
				}
			} );

		}
	}

	protected function doRefreshStore( $refreshjob ) {

		if ( $GLOBALS['smwgAdminRefreshStore'] ) {

			$sure = $GLOBALS['wgRequest']->getText( 'rfsure' );
			$title = SpecialPage::getTitleFor( 'SMWAdmin' );

			if ( $sure == 'yes' ) {
				if ( is_null( $refreshjob ) ) { // careful, there might be race conditions here
					$newjob = new RefreshJob( $title, array( 'spos' => 1, 'prog' => 0, 'rc' => 2 ) );
					$newjob->insert();
					// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
					$this->getOutput()->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatestarted', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
					// @codingStandardsIgnoreEnd
				} else {
					// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
					$this->getOutput()->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatenotstarted', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
					// @codingStandardsIgnoreEnd
				}

			} elseif ( $sure == 'stop' ) {

				// FIXME See above comments !!

				$dbw = wfGetDB( DB_MASTER );
				// delete (all) existing iteration jobs
				$dbw->delete( 'job', array( 'job_cmd' => 'SMW\RefreshJob' ), __METHOD__ );
				// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
				$this->getOutput()->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatestopped', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
				// @codingStandardsIgnoreEnd
			} else {
				// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
				$this->getOutput()->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatenotstopped', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
				// @codingStandardsIgnoreEnd
			}

		}

	}

	protected function doListConfigurationSettings() {
		$this->printRawOutput( function( $instance ) {
			print '<pre>' . $instance->encodeJson( Settings::newFromGlobals()->getOptions() ) . '</pre>';
		} );
	}

	protected function doIdDispose( $id ) {

		if ( $GLOBALS['wgRequest']->getText( 'dispose' ) !== 'yes' || $id < 1 ) {
			return $id;
		}

		$propertyTableIdReferenceDisposer = new PropertyTableIdReferenceDisposer(
			ApplicationFactory::getInstance()->getStore( '\SMW\SQLStore\SQLStore' )
		);

		$propertyTableIdReferenceDisposer->cleanupTableEntriesFor( $id );

		$manualEntryLogger = new ManualEntryLogger();
		$manualEntryLogger->registerLoggableEventType( 'admin' );
		$manualEntryLogger->log( 'admin', $this->getUser(), 'Special:SMWAdmin', 'Forced removal of ID '. $id );
	}

	protected function printRawOutput( $text ) {
		$this->getOutput()->disable(); // raw output
		ob_start();

		// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
		print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>Semantic MediaWiki</title></head><body><p><pre>";
		// @codingStandardsIgnoreEnd
		// header( "Content-type: text/html; charset=UTF-8" );
		is_callable( $text ) ? $text( $this ) : $text();
		print '</pre></p>';
		// @codingStandardsIgnoreStart phpcs, ignore --sniffs=Generic.Files.LineLength.MaxExceeded
		print '<b> ' . wfMessage( 'smw_smwadmin_return', '<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'SMWAdmin' )->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . "</b>\n";
		// @codingStandardsIgnoreEnd
		print '</body></html>';

		ob_flush();
		flush();
	}

	/**
	 * @note JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES, and
	 * JSON_UNESCAPED_UNICOD were only added with 5.4
	 */
	public function encodeJson( array $input ) {

		if ( defined( 'JSON_PRETTY_PRINT' ) ) {
			return json_encode( $input, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		return FormatJson::encode( $input, true );
	}

	protected function getGroupName() {
		return 'smw_group';
	}
}
