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
		parent::__construct('SMWAdmin', 'delete');
		wfLoadExtensionMessages('SemanticMediaWiki');
	}

	public function execute($par = null) {
		global $wgOut, $wgRequest, $smwgAdminRefreshStore;
		global $wgServer; // "http://www.yourserver.org"
							// (should be equal to 'http://'.$_SERVER['SERVER_NAME'])
		global $wgScript;   // "/subdirectory/of/wiki/index.php"
		global $wgUser;
	
		if ( ! $wgUser->isAllowed('delete') ) {
			$wgOut->permissionRequired('delete');
			return;
		}

		$this->setHeaders();

		/**** Get status of refresh job, if any ****/
		if ($smwgAdminRefreshStore) {
			$dbr =& wfGetDB( DB_SLAVE );
			$row = $dbr->selectRow( 'job', '*', array( 'job_cmd' => 'SMWRefreshJob' ), __METHOD__ );
			if ($row !== false) { // similar to Job::pop_type, but without deleting the job
				$title = Title::makeTitleSafe( $row->job_namespace, $row->job_title);
				$refreshjob = Job::factory( $row->job_cmd, $title, Job::extractBlob( $row->job_params ), $row->job_id );
			} else {
				$refreshjob = NULL;
			}
		}

		/**** Execute actions if any ****/

		$action = $wgRequest->getText( 'action' );
		if ( $action=='updatetables' ) {
			$sure = $wgRequest->getText( 'udsure' );
			if ($sure == 'yes') {
				$wgOut->disable(); // raw output
				ob_start();
				print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\" dir=\"ltr\">\n<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><title>Setting up Storage for Semantic MediaWiki</title></head><body><p><pre>";
				header( "Content-type: text/html; charset=UTF-8" );
				$result = smwfGetStore()->setup();
				wfRunHooks('smwInitializeTables');
				print '</pre></p>';
				if ($result === true) {
					print '<p><b>The storage engine was set up successfully.</b></p>';
				}
				$returntitle = Title::makeTitle(NS_SPECIAL, 'SMWAdmin');
				print '<p> Return to <a href="' . htmlspecialchars($returntitle->getFullURL()) . '">Special:SMWAdmin</a></p>';
				print '</body></html>';
				ob_flush();
				flush();
				return;
			}
		} elseif ($smwgAdminRefreshStore && ($action=='refreshstore')) { // managing refresh jobs for the store
			$sure = $wgRequest->getText( 'rfsure' );
			if ($sure == 'yes') {
				if ($refreshjob === NULL) { // careful, there might be race conditions here
					$title = Title::makeTitle(NS_SPECIAL, 'SMWAdmin');
					$newjob = new SMWRefreshJob($title, array('spos'=>1));
					$newjob->insert();
					$wgOut->addHTML("<p>A new update process for refreshing the semantic data was started. All stored data will be rebuilt or repaired where needed.</p>");
				} else {
					$wgOut->addHTML("<p>There is already an update process running. Not creating another one.</p>");
				}
			} elseif ($sure == 'stop') {
				$dbw =& wfGetDB( DB_MASTER );
				// delete (all) existing iteration jobs
				$dbw->delete( 'job', array( 'job_cmd' => 'SMWRefreshJob' ), __METHOD__ );
				$wgOut->addHTML("<p>All existing update processes have been stopped.</p>");
			} else {
				$wgOut->addHTML("<p>To stop the running update process, you must activate the checkbox to indicate that you are really sure.</p>");
			}
			return;
		}

		/**** Normal output ****/

		$html = '<p>This special page helps you during installation and upgrade of 
					<a href="http://semantic-mediawiki.org">Semantic MediaWiki</a>. Remember to backup valuable data before 
					executing administrative functions.</p>' . "\n";
		// creating tables and converting contents from older versions
		$html .= '<form name="buildtables" action="" method="POST">' . "\n" .
				'<input type="hidden" name="action" value="updatetables" />' . "\n";
		$html .= '<h2>Preparing database for Semantic MediaWiki</h2>' . "\n" .
				'<p>Semantic MediaWiki requires some extensions to the MediaWiki database in 
				order to store the semantic data. The below function ensures that your database is
				set up properly. The changes made in this step do not affect the rest of the 
				MediaWiki database, and can easily be undone if desired. This setup function
				can be executed multiple times without doing any harm, but it is needed only once on
				installation or upgrade.<p/>' . "\n";
		$html .= '<p>If the operation fails with SQL errors, the database user employed 
				by your wiki (check your LocalSettings.php) probably does not have sufficient 
				permissions. Either grant this user additional persmissions to create and delete 
				tables, temporarily enter the login of your database root in LocalSettings.php, or use the maintenance script <tt>SMW_setup.php</tt> which can use the credentials of AdminSettings.php.<p/>' .
				"\n" . '<input type="hidden" name="udsure" value="yes"/>' .
				'<input type="submit" value="Initialise or upgrade tables"/></form>' . "\n";

		$html .= '<h2>Announce your wiki</h2>' . "\n" . 
				'<p>SMW has a web service for announcing new semantic wiki sites. This is used to maintain a list of public sites that use SMW, mainly to help the <a href="http://semantic-mediawiki.org/wiki/SMW_Project">SMW project</a> to get an overview of typical uses of SMW. See the SMW homepage for <a href="http://semantic-mediawiki.org/wiki/Registry">further information about this service.</a></p>' .
				'<p>Press the following button to submit your wiki URL to that service. The service will not register wikis that are not publicly accessible, and it will only store publicly accessible information.</p>
				 <form name="announcewiki" action="http://semantic-mediawiki.org/wiki/Special:SMWRegistry" method="GET">' .
				 '<input type="hidden" name="url" value="' . SMWExporter::expandURI('&wikiurl;') . '" />' .
				 '<input type="hidden" name="return" value="Special:SMWAdmin" />' .
				 '<input type="submit" value="Announce wiki"/></form>' . "\n";

		if ($smwgAdminRefreshStore) {
			$html .= '<h2>Repair and Upgrade</h2>' . "\n" .
					'<p>It is possible to restore all SMW data based on the current contents of the wiki. This can be useful to repair broken data or to refresh the data if the internal format has changed due to some software upgrade. The update is executed page by page and will not be completed immediately. The following control shows if an update is in progress and allows you to start or stop upates.</p>' .
					'<form name="refreshwiki" action="" method="POST">';
			if ($refreshjob !== NULL) {
				$prog = $refreshjob->getProgress();
				$html .= "<p><b>An update is already in progress.</b> It is normal that the update progresses only slowly. This reduces server load. To finish an update more quickly, you can use the MediaWiki maintenance script <tt>runJobs.php</tt> (use the option <tt>--maxjobs 2000</tt> to restrict the number of updates done at once). Estimated progress of current update:</p> " .
					'<p><div style="float: left; background: #DDDDDD; border: 1px solid grey; width: 300px; "><div style="background: #AAF; width: ' .
					round($prog*300) . 'px; height: 20px; "> </div></div> &nbsp;' . round($prog*100,4) . '%</p><br /><br />' .
					'<input type="hidden" name="action" value="refreshstore" />' .
					'<input type="submit" value="Stop ongoing update"/> ' .
					' <input type="checkbox" name="rfsure" value="stop"/> Yes, I am sure. ' .
					'</form>' . "\n";
			} else {
				$html .=
					'<input type="hidden" name="action" value="refreshstore" />' .
					'<input type="hidden" name="rfsure" value="yes"/>' .
					'<input type="submit" value="Start updating data"/>' .
					'</form>' . "\n";
			}
		}


		$html .= '<h2>Getting support</h2>' . "\n" . 
				'<p>Various resources might help you in case of problems:</p>
				<ul>
				<li> If you experience problems with your installation, start by checking the guidelines in the <a href="http://svn.wikimedia.org/svnroot/mediawiki/trunk/extensions/SemanticMediaWiki/INSTALL">INSTALL file</a>.</li>
				<li>The complete user documentation to Semantic MediaWiki is at <b><a href="http://semantic-mediawiki.org">semantic-mediawiki.org</a></b>.</li>
				<li>Bugs can be reported to <a href="http://bugzilla.wikimedia.org/">MediaZilla</a>.</li>
				<li>If you have further questions or suggestions, join the discussion on <a href="mailto:semediawiki-user@lists.sourceforge.net">semediawiki-user@lists.sourceforge.net</a>.</li>
				<ul/>' . "\n";

		$wgOut->addHTML($html);
	}

}


