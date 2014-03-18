<?php

use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\Settings;
use SMW\StoreFactory;
use SMW\Store;

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

	public function __construct() {
		parent::__construct( 'SMWAdmin', 'smw-admin' );
		$this->store = StoreFactory::getStore();
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

		// FIXME Searching the job table needs to be fixed
		//
		// SMW shouldn't expose itself to an internal MW DB table at
		// this level. If an official Api doesn't provide needed
		// functionality, the DB call should be encapsulate within its
		// own class

		/**** Get status of refresh job, if any ****/
		$dbr = wfGetDB( DB_SLAVE );
		$row = $dbr->selectRow( 'job', '*', array( 'job_cmd' => 'SMW\RefreshJob' ), __METHOD__ );
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
				return $this->actionListSettings();
			case 'idlookup':
				return $this->actionIdLookup( $this->getRequest()->getVal( 'objectId' ) );
			case 'updatetables':
				return $this->actionUpdateTables();
			case 'refreshstore':
				return $this->actionRefreshStore( $refreshjob );
		}

		/**** Normal output ****/

		$html = '<p>' . wfMessage( 'smw_smwadmin_docu' )->text() . "</p>\n";
		// creating tables and converting contents from older versions
		$html .= '<form name="buildtables" action="" method="POST">' . "\n" .
				'<input type="hidden" name="action" value="updatetables" />' . "\n";
		$html .= '<br /><h2>' . wfMessage( 'smw_smwadmin_db' )->text() . "</h2>\n" .
				'<p>' . wfMessage( 'smw_smwadmin_dbdocu' )->text() . "</p>\n";
		$html .= '<p>' . wfMessage( 'smw_smwadmin_permissionswarn' )->text() . "</p>\n" .
				'<input type="hidden" name="udsure" value="yes"/>' .
				'<input type="submit" value="' . wfMessage( 'smw_smwadmin_dbbutton' )->text() . '"/></form>' . "\n";

		$html .= '<br /><h2>' . wfMessage( 'smw_smwadmin_datarefresh' )->text() . "</h2>\n" .
				'<p>' . wfMessage( 'smw_smwadmin_datarefreshdocu' )->text() . "</p>\n";
		if ( !is_null( $refreshjob ) ) {
			$prog = $refreshjob->getProgress();
			$html .= '<p>' . wfMessage( 'smw_smwadmin_datarefreshprogress' )->text() . "</p>\n" .
			'<p><div style="float: left; background: #DDDDDD; border: 1px solid grey; width: 300px; "><div style="background: #AAF; width: ' .
				round( $prog * 300 ) . 'px; height: 20px; "> </div></div> &#160;' . round( $prog * 100, 4 ) . '%</p><br /><br />';
			if ( $GLOBALS['smwgAdminRefreshStore'] ) {
				$html .=
				'<form name="refreshwiki" action="" method="POST">' .
				'<input type="hidden" name="action" value="refreshstore" />' .
				'<input type="submit" value="' . wfMessage( 'smw_smwadmin_datarefreshstop' )->escaped() . '" /> ' .
				' <input type="checkbox" name="rfsure" value="stop"/> ' . wfMessage( 'smw_smwadmin_datarefreshstopconfirm' )->escaped() .
				'</form>' . "\n";
			}
		} elseif ( $GLOBALS['smwgAdminRefreshStore'] ) {
			$html .=
				'<form name="refreshwiki" action="" method="POST">' .
				'<input type="hidden" name="action" value="refreshstore" />' .
				'<input type="hidden" name="rfsure" value="yes"/>' .
				'<input type="submit" value="' . wfMessage( 'smw_smwadmin_datarefreshbutton' )->text() . '"/>' .
				'</form>' . "\n";
		}

		$html .= $this->getSettingsSection();
		$html .= $this->getIdLookupSection();
		$html .= $this->getAnnounceSection();
		$html .= $this->getSupportSection();

		$this->getOutput()->addHTML( $html );
	}

	protected function getSettingsSection() {
		return '<br /><h2>' . $this->msg( 'smw-sp-admin-settings-title' )->text() . "</h2>\n" .
			'<p>' . $this->msg( 'smw-sp-admin-settings-docu' )->parse() . "</p>\n".
			'<form name="listsettings" action="" method="POST">' .
			'<input type="hidden" name="action" value="listsettings" />' .
			'<input type="submit" value="' . $this->msg( 'smw-sp-admin-settings-button' )->text() . '"/>' .
			'</form>' . "\n";
	}

	protected function getAnnounceSection() {
		return '<br /><h2>' . wfMessage( 'smw_smwadmin_announce' )->text() . "</h2>\n" .
			'<p>' . wfMessage( 'smw_smwadmin_announcedocu' )->text() . "</p>\n" .
			'<p>' . wfMessage( 'smw_smwadmin_announcebutton' )->text() . "</p>\n" .
			 '<form name="announcewiki" action="http://semantic-mediawiki.org/wiki/Special:SMWRegistry" method="GET">' .
			 '<input type="hidden" name="url" value="' . $GLOBALS['wgServer'] . str_replace( '$1', '', $GLOBALS['wgArticlePath'] ) . '" />' .
			 '<input type="hidden" name="return" value="Special:SMWAdmin" />' .
			 '<input type="submit" value="' . wfMessage( 'smw_smwadmin_announce' )->text() . '"/></form>' . "\n";
	}

	protected function getSupportSection() {
		return '<br /><h2>' . wfMessage( 'smw_smwadmin_support' )->text() . "</h2>\n" .
			'<p>' . wfMessage( 'smw_smwadmin_supportdocu' )->text() . "</p>\n" .
			"<ul>\n" .
			'<li>' . wfMessage( 'smw_smwadmin_installfile' )->text() . "</li>\n" .
			'<li>' . wfMessage( 'smw_smwadmin_smwhomepage' )->text() . "</li>\n" .
			'<li>' . wfMessage( 'smw_smwadmin_mediazilla' )->text() . "</li>\n" .
			'<li>' . wfMessage( 'smw_smwadmin_questions' )->text() . "</li>\n" .
			"</ul>\n";
	}

	protected function getIdLookupSection() {

		return '<br />' .
			Html::element( 'h2', array(), $this->msg( 'smw-sp-admin-idlookup-title' )->text() ) . "\n" .
			Html::element( 'p', array(), $this->msg( 'smw-sp-admin-idlookup-docu' )->text() ) . "\n" .
			Xml::tags( 'form', array(
				'method' => 'get',
				'action' => $GLOBALS['wgScript']
			),
			Html::hidden( 'title', $this->getContext()->getTitle()->getPrefixedText() ) .
				Html::hidden( 'action', 'idlookup' ) .
				Xml::inputLabel( $this->msg( 'smw-sp-admin-idlookup-objectid' )->text(), 'objectId', 'objectId', 20, null ) . ' ' .
				Xml::submitButton( $this->msg( 'allpagessubmit' )->text() )
			);
	}

	protected function actionUpdateTables() {
		if ( $GLOBALS['wgRequest']->getText( 'udsure' ) == 'yes' ) {

			$this->printRawOutput( function() {
				$result = SMWStore::setupStore();
				if ( $result === true ) {
					print '<p><b>' . wfMessage( 'smw_smwadmin_setupsuccess' )->text() . "</b></p>\n";
				}
			} );

		}
	}

	protected function actionRefreshStore( $refreshjob ) {

		if ( $GLOBALS['smwgAdminRefreshStore'] ) {

			$sure = $GLOBALS['wgRequest']->getText( 'rfsure' );
			$title = SpecialPage::getTitleFor( 'SMWAdmin' );

			if ( $sure == 'yes' ) {
				if ( is_null( $refreshjob ) ) { // careful, there might be race conditions here
					$newjob = new RefreshJob( $title, array( 'spos' => 1, 'prog' => 0, 'rc' => 2 ) );
					$newjob->insert();
					$this->getOutput()->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatestarted', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
				} else {
					$this->getOutput()->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatenotstarted', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
				}

			} elseif ( $sure == 'stop' ) {

				// FIXME See above comments !!

				$dbw = wfGetDB( DB_MASTER );
				// delete (all) existing iteration jobs
				$dbw->delete( 'job', array( 'job_cmd' => 'SMW\RefreshJob' ), __METHOD__ );
				$this->getOutput()->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatestopped', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
			} else {
				$this->getOutput()->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatenotstopped', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
			}

		}

	}

	protected function actionListSettings() {
		$this->printRawOutput( function( $instance ) {
			print '<pre>' . $instance->encodeJson( Settings::newFromGlobals()->toArray() ) . '</pre>';
		} );
	}

	protected function actionIdLookup( $objectId ) {
		$objectId = (int)$objectId;

		$this->printRawOutput( function( $instance ) use ( $objectId ) {

			$tableName = $instance->getStore()->getObjectIds()->getIdTable();

			$row = $instance->getStore()->getDatabase()->selectRow(
					$tableName,
					array(
						'smw_title',
						'smw_namespace',
						'smw_iw',
						'smw_subobject'
					),
					'smw_id=' . $objectId,
					__METHOD__
			);

			print '<pre>' . $instance->encodeJson( array( $objectId, $row ) ) . '</pre>';
		} );
	}

	protected function printRawOutput( $text ) {
		$this->getOutput()->disable(); // raw output
		ob_start();

		print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>Semantic MediaWiki</title></head><body><p><pre>";
		// header( "Content-type: text/html; charset=UTF-8" );
		is_callable( $text ) ? $text( $this ) : $text;
		print '</pre></p>';
		print '<b> ' . wfMessage( 'smw_smwadmin_return', '<a href="' . htmlspecialchars( SpecialPage::getTitleFor( 'SMWAdmin' )->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . "</b>\n";
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

}
