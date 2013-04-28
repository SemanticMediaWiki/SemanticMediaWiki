<?php
/**
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */

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
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'SMWAdmin', 'delete' );
	}

	public function execute( $par ) {
		global $wgOut, $wgRequest, $wgServer, $wgArticlePath, $wgUser, $smwgAdminRefreshStore;

		if ( !$this->userCanExecute( $wgUser ) ) {
			// If the user is not authorized, show an error.
			$this->displayRestrictionError();
			return;
		}

		$this->setHeaders();

		/**** Get status of refresh job, if any ****/
		$dbr = wfGetDB( DB_SLAVE );
		$row = $dbr->selectRow( 'job', '*', array( 'job_cmd' => 'SMWRefreshJob' ), __METHOD__ );
		if ( $row !== false ) { // similar to Job::pop_type, but without deleting the job
			$title = Title::makeTitleSafe( $row->job_namespace, $row->job_title );
			$blob = (string)$row->job_params !== '' ? unserialize( $row->job_params ) : false;
			$refreshjob = Job::factory( $row->job_cmd, $title, $blob, $row->job_id );
		} else {
			$refreshjob = null;
		}

		/**** Execute actions if any ****/
		$action = $wgRequest->getText( 'action' );
		if ( $action == 'updatetables' ) {
			$sure = $wgRequest->getText( 'udsure' );
			if ( $sure == 'yes' ) {
				$wgOut->disable(); // raw output
				ob_start();
				print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>Setting up Storage for Semantic MediaWiki</title></head><body><p><pre>";
				header( "Content-type: text/html; charset=UTF-8" );
				$result = SMWStore::setupStore();
				print '</pre></p>';
				if ( $result === true ) {
					print '<p><b>' . wfMessage( 'smw_smwadmin_setupsuccess' )->text() . "</b></p>\n";
				}
				$returntitle = SpecialPage::getTitleFor( 'SMWAdmin' );
				print '<p> ' . wfMessage( 'smw_smwadmin_return', '<a href="' . htmlspecialchars( $returntitle->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . "</p>\n";
				print '</body></html>';
				ob_flush();
				flush();
				return;
			}
		} elseif ( $smwgAdminRefreshStore && ( $action == 'refreshstore' ) ) { // managing refresh jobs for the store
			$sure = $wgRequest->getText( 'rfsure' );
			$title = SpecialPage::getTitleFor( 'SMWAdmin' );
			if ( $sure == 'yes' ) {
				if ( is_null( $refreshjob ) ) { // careful, there might be race conditions here
					$newjob = new SMWRefreshJob( $title, array( 'spos' => 1, 'prog' => 0, 'rc' => 2 ) );
					$newjob->insert();
					$wgOut->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatestarted', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
				} else {
					$wgOut->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatenotstarted', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
				}
			} elseif ( $sure == 'stop' ) {
				$dbw = wfGetDB( DB_MASTER );
				// delete (all) existing iteration jobs
				$dbw->delete( 'job', array( 'job_cmd' => 'SMWRefreshJob' ), __METHOD__ );
				$wgOut->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatestopped', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
			} else {
				$wgOut->addHTML( '<p>' . wfMessage( 'smw_smwadmin_updatenotstopped', '<a href="' . htmlspecialchars( $title->getFullURL() ) . '">Special:SMWAdmin</a>' )->text() . '</p>' );
			}
			return;
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
			if ( $smwgAdminRefreshStore ) {
				$html .=
				'<form name="refreshwiki" action="" method="POST">' .
				'<input type="hidden" name="action" value="refreshstore" />' .
				'<input type="submit" value="' . wfMessage( 'smw_smwadmin_datarefreshstop' )->escaped() . '" /> ' .
				' <input type="checkbox" name="rfsure" value="stop"/> ' . wfMessage( 'smw_smwadmin_datarefreshstopconfirm' )->escaped() .
				'</form>' . "\n";
			}
		} elseif ( $smwgAdminRefreshStore ) {
			$html .=
				'<form name="refreshwiki" action="" method="POST">' .
				'<input type="hidden" name="action" value="refreshstore" />' .
				'<input type="hidden" name="rfsure" value="yes"/>' .
				'<input type="submit" value="' . wfMessage( 'smw_smwadmin_datarefreshbutton' )->text() . '"/>' .
				'</form>' . "\n";
		}

		$html .= '<br /><h2>' . wfMessage( 'smw_smwadmin_announce' )->text() . "</h2>\n" .
				'<p>' . wfMessage( 'smw_smwadmin_announcedocu' )->text() . "</p>\n" .
				'<p>' . wfMessage( 'smw_smwadmin_announcebutton' )->text() . "</p>\n" .
				 '<form name="announcewiki" action="http://semantic-mediawiki.org/wiki/Special:SMWRegistry" method="GET">' .
				 '<input type="hidden" name="url" value="' . $wgServer . str_replace( '$1', '', $wgArticlePath ) . '" />' .
				 '<input type="hidden" name="return" value="Special:SMWAdmin" />' .
				 '<input type="submit" value="' . wfMessage( 'smw_smwadmin_announce' )->text() . '"/></form>' . "\n";

		$html .= '<br /><h2>' . wfMessage( 'smw_smwadmin_support' )->text() . "</h2>\n" .
				'<p>' . wfMessage( 'smw_smwadmin_supportdocu' )->text() . "</p>\n" .
				"<ul>\n" .
				'<li>' . wfMessage( 'smw_smwadmin_installfile' )->text() . "</li>\n" .
				'<li>' . wfMessage( 'smw_smwadmin_smwhomepage' )->text() . "</li>\n" .
				'<li>' . wfMessage( 'smw_smwadmin_mediazilla' )->text() . "</li>\n" .
				'<li>' . wfMessage( 'smw_smwadmin_questions' )->text() . "</li>\n" .
				"</ul>\n";

		$wgOut->addHTML( $html );
	}
}
