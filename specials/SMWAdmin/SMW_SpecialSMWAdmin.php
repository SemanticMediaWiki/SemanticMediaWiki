<?php

/**
 * @author Markus Krötzsch
 *
 * This special page for MediaWiki provides an administrative interface 
 * that allows to execute certain functions related to the maintainance 
 * of the semantic database. It is restricted to users with siteadmin status.
 *
 * @note AUTOLOAD
 */
class SMWAdmin extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wgMessageCache; ///TODO: should these be messages?
		$wgMessageCache->addMessages(array('smwadmin' => 'Admin functions for Semantic MediaWiki'));
		parent::__construct('SMWAdmin', 'delete');
	}

	public function execute($par = null) {
		global $wgOut, $wgRequest;
		global $wgServer; // "http://www.yourserver.org"
							// (should be equal to 'http://'.$_SERVER['SERVER_NAME'])
		global $wgScript;   // "/subdirectory/of/wiki/index.php"
		global $wgUser;
	
		if ( ! $wgUser->isAllowed('delete') ) {
			$wgOut->permissionRequired('delete');
			return;
		}
	
		$wgOut->setPageTitle(wfMsg('smwadmin'));
	
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
				$returntitle = Title::newFromText('Special:SMWAdmin');
				print '<p> Return to <a href="' . htmlspecialchars($returntitle->getFullURL()) . '">Special:SMWAdmin</a></p>';
				print '</body></html>';
				ob_flush();
				flush();
				return;
			}
		}
	
		/**** Normal output ****/
	
		$html = '<p>This special page helps you during installation and upgrade of 
					<a href="http://semantic-mediawiki.org">Semantic MediaWiki</a>. Remember to backup valuable data before 
					executing administrative functions.</p>' . "\n";
		// creating tables and converting contents from older versions
		$html .= '<form name="buildtables" action="" method="POST">' . "\n" .
				'<input type="hidden" name="action" value="updatetables" />' . "\n";
		$html .= '<h2>Preparing database for Semantic MediaWiki</h2>' . "\n" .
				'<p>Semantic MediaWiki requires some minor extensions to the MediaWiki database in 
				order to store the semantic data. The below function ensures that your database is
				set up properly. The changes made in this step do not affect the rest of the 
				MediaWiki database, and can easily be undone if desired. This setup function
				can be executed multiple times without doing any harm, but it is needed only once on
				installation or upgrade.<p/>' . "\n";
		$html .= '<p>If the operation fails with obscure SQL errors, the database user employed 
				by your wiki (check your LocalSettings.php) probably does not have sufficient 
				permissions. Either grant this user additional persmissions to create and delete 
				tables, or temporarily enter the login of your database root in LocalSettings.php.<p/>' .
				"\n" . '<input type="hidden" name="udsure" value="yes"/>' .
				'<input type="submit" value="Initialise or upgrade tables"/></form>' . "\n";

		$html .= '<h2>Announce your wiki</h2>' . "\n" . 
				'<p>SMW has a web service for announcing new semantic wiki sites. This is used to maintain a list of public sites that use SMW, mainly to help the <a href="http://semantic-mediawiki.org/wiki/SMW_Project">SMW project</a> to get an overview of typical uses of SMW. See the SMW homepage for <a href="http://semantic-mediawiki.org/wiki/Registry">further information about this service.</a>' .
				'<p>Press the following button to submit your wiki URL to that service. The service will not register wikis that are not publicly accessible, and it will only store publicly accessible information.</p>
				 <form name="announcewiki" action="http://semantic-mediawiki.org/wiki/Special:SMWRegistry" method="GET">' .
				 '<input type="hidden" name="url" value="' . SMWExporter::expandURI('&wikiurl;') . '" />' .
				 '<input type="hidden" name="return" value="Special:SMWAdmin" />' .
				 '<input type="submit" value="Announce wiki"/></form>' . "\n";
	
		$html .= '<h2>Getting support</h2>' . "\n" . 
				'<p>Various resources might help you in case of problems:</p>
				<ul>
				<li> If you experience problems with your installation, start by checking the guidelines in the <a href="http://svn.wikimedia.org/svnroot/mediawiki/trunk/extensions/SemanticMediaWiki/INSTALL">INSTALL file</a>.</li>
				<li>The complete user documentation to Semantic MediaWiki is at <b><a href="http://semantic-mediawiki.org">semantic-mediawiki.org</a></b>.</li>
				<li>Bugs can be reported to <a href="http://bugzilla.wikimedia.org/">MediaZilla</a>.</li>
				<li>If you have further questions or suggestions, join the discussion on <a href="mailto:semediawiki-user@lists.sourceforge.net">semediawiki-user@lists.sourceforge.net</a>.</li>
				<ul/>' . "\n";
		
		$wgOut->addHTML($html);
		return true;
	}

}

// // Special function for fixing development testing DBs after messing around ...
// function smwfRestoreTableTitles() {
// 	$dbr =& wfGetDB( DB_SLAVE );
// 	extract( $dbr->tableNames( 'smw_attributes','smw_relations','smw_specialprops' ));
// 	
// 	// convert old values
// 	$sql = 'SELECT DISTINCT attribute_title FROM ' . $smw_attributes;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
// 			if ($t != NULL) { 
// 				$text = $t->getDBkey(); 
// 				$sql = "UPDATE $smw_attributes SET attribute_title = " . $dbr->addQuotes($text) . " WHERE attribute_title = " . $dbr->addQuotes($row->attribute_title) ;
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);			
// 		}
// 	}	
// 	
// 	// convert old values
// 	$sql = 'SELECT DISTINCT relation_title FROM ' . $smw_relations;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->relation_title, SMW_NS_RELATION);
// 			if ($t != NULL) { 
// 				$text = $t->getDBkey(); 
// 				$sql = "UPDATE $smw_relations SET relation_title = " . $dbr->addQuotes($text) . " WHERE relation_title = " . $dbr->addQuotes($row->relation_title) ;
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);			
// 		}
// 	}
// 	
// 	// convert old values
// 	$sql = 'SELECT DISTINCT subject_title, subject_namespace FROM ' . $smw_attributes;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
// 			if ($t != NULL) { 
// 				$id = $t->getArticleID(); 
// 				$stitle = $t->getDBkey(); 
// 				$sql = "UPDATE $smw_attributes SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title);
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);			
// 		}
// 	}	
// 	
// 	// convert old values
// 	$sql = 'SELECT DISTINCT subject_title, subject_namespace, object_title FROM ' . $smw_relations;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
// 			if ($t != NULL) { 
// 				$id = $t->getArticleID(); 
// 				$stitle = $t->getDBkey(); 
// 				$sql = "UPDATE $smw_relations SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . ", object_title = " . $dbr->addQuotes(str_replace(" ", "_", $row->object_title)) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title) . " AND object_title = " . $dbr->addQuotes($row->object_title);
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);
// 		}
// 	}	
// 	
// 	// convert old values
// 	$sql = 'SELECT DISTINCT subject_title, subject_namespace FROM ' . $smw_specialprops;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
// 			if ($t != NULL) { 
// 				$id = $t->getArticleID(); 
// 				$stitle = $t->getDBkey();
// 				$sql = "UPDATE $smw_specialprops SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title);
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);
// 		}
// 	}
// 	
// 	//add more indices for new columns
// // 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `attribute_title` )";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_num` )";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_xsd` )";
// // 	$dbr->query( $sql, $fname );	
// // 	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `relation_title` )";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `object_title` )";
// // 	$dbr->query( $sql, $fname );
// 	
// 	
// // 	// modify table structure for attributes
// // 	$sql = "ALTER TABLE $smw_attributes CHANGE `subject_title` `subject_title` VARCHAR(255) NOT NULL";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_attributes CHANGE `attribute_title` `attribute_title` VARCHAR(255) NOT NULL";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_attributes CHANGE `value_unit` `value_unit` VARCHAR(63)";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_attributes CHANGE `value_datatype` `value_datatype` VARCHAR(31) NOT NULL";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_attributes CHANGE `value_xsd` `value_xsd` VARCHAR(255) NOT NULL";
// // 	$dbr->query( $sql, $fname );
// // 	
// // 	$sql = "ALTER TABLE $smw_relations CHANGE `subject_title` `subject_title` VARCHAR(255) NOT NULL";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_relations CHANGE `relation_title` `relation_title` VARCHAR(255) NOT NULL";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_relations CHANGE `object_title` `object_title` VARCHAR(255) NOT NULL";
// // 	$dbr->query( $sql, $fname );	
// // 	
// // 	$sql = "ALTER TABLE $smw_specialprops CHANGE `subject_title` `subject_title` VARCHAR(255) NOT NULL";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_specialprops CHANGE `value_string` `value_string` VARCHAR(255) NOT NULL";
// // 	$dbr->query( $sql, $fname );
// 	
// 	return "Done";
// }

// /**
//  * A function for updating tables from 0.3 to post 0.3
//  */
// function smwfAdminUpdateTables() {
// 	$dbr =& wfGetDB( DB_MASTER );
// 
// 	if ($dbr->tableExists('smw_relations') === false) {
// 		smwfGetStore()->setup();
// 		return 'The database has been initialised successfully.';
// 	}
// 
// 	if ($dbr->fieldExists('smw_relations', 'subject_id')) {
// 		return 'This function was probably called accidentally. Your database already has the required structure.';
// 	}
// 	
// 	extract( $dbr->tableNames( 'smw_attributes','smw_relations','smw_specialprops' ));
// 	$fname = 'SMW::AdminUpdateTables';
// 	
// 	// modify table structure for attributes
// 	$sql = "ALTER TABLE $smw_attributes TYPE = innodb";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes ADD `subject_id` INT( 8 ) UNSIGNED NOT NULL FIRST";	
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `subjectns` `subject_namespace` INT( 11 ) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `subject` `subject_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `attribute` `attribute_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `unit` `value_unit` VARCHAR(63)";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `datatype` `value_datatype` VARCHAR(31) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `value` `value_xsd` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes ADD `value_num` DOUBLE";
// 	$dbr->query( $sql, $fname );
// 	
// 	// convert old values
// 	$sql = 'SELECT DISTINCT subject_title, subject_namespace FROM ' . $smw_attributes;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
// 			if ($t != NULL) { 
// 				$id = $t->getArticleID(); 
// 				$stitle = $t->getDBkey(); 
// 				$sql = "UPDATE $smw_attributes SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title);
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);			
// 		}
// 	}
// 	
// 	// create new scalar representations
// 	// (luckily, for all 0.3 datatypes that have a scalar representation, the
// 	// scalar version has the same PHP representation as the textual one. 
// 	foreach (array('int','float','geoarea','geolength') as $type) {
// 		$sql = "SELECT DISTINCT value_xsd FROM $smw_attributes WHERE value_datatype = " . $dbr->addQuotes($type);
// 		$res = $dbr->query( $sql, $fname );
// 		if($dbr->numRows( $res ) > 0) {
// 			$row = $dbr->fetchObject($res);
// 			while($row) {
// 				$sql = "UPDATE $smw_attributes SET value_num = $row->value_xsd WHERE value_xsd = $row->value_xsd AND value_datatype = " . $dbr->addQuotes($type);
// 				$dbr->query( $sql, $fname );
// 				
// 				$row = $dbr->fetchObject($res);
// 			}
// 		}
// 	}
// 	
// 	// rename attributes according to their DBkey
// 	$sql = 'SELECT DISTINCT attribute_title FROM ' . $smw_attributes;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
// 			if ($t != NULL) { 
// 				$text = $t->getDBkey(); 
// 				$sql = "UPDATE $smw_attributes SET attribute_title = " . $dbr->addQuotes($text) . " WHERE attribute_title = " . $dbr->addQuotes($row->attribute_title) ;
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);
// 		}
// 	}
// 	
// 	// modify table structure for relations
// 	$sql = "ALTER TABLE $smw_relations TYPE = innodb";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations ADD `subject_id` INT( 8 ) UNSIGNED NOT NULL FIRST";	
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations CHANGE `subjectns` `subject_namespace` INT( 11 ) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations CHANGE `subject` `subject_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations CHANGE `relation` `relation_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations CHANGE `objectns` `object_namespace` INT( 11 ) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations CHANGE `object` `object_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	
// 	// convert old values
// 	$sql = 'SELECT DISTINCT subject_title, subject_namespace, object_title FROM ' . $smw_relations;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
// 			if ($t != NULL) { 
// 				$id = $t->getArticleID(); 
// 				$stitle = $t->getDBkey(); 
// 				$sql = "UPDATE $smw_relations SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . ", object_title = " . $dbr->addQuotes(str_replace(" ", "_", $row->object_title)) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title) . " AND object_title = " . $dbr->addQuotes($row->object_title);
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);
// 		}
// 	}
// 	
// 	// convert old values
// 	$sql = 'SELECT DISTINCT relation_title FROM ' . $smw_relations;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->relation_title, SMW_NS_RELATION);
// 			if ($t != NULL) { 
// 				$text = $t->getDBkey(); 
// 				$sql = "UPDATE $smw_relations SET relation_title = " . $dbr->addQuotes($text) . " WHERE relation_title = " . $dbr->addQuotes($row->relation_title) ;
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);	
// 		}
// 	}
// 	
// 	
// 	// delete obsolete categorisation values; in the future, we will use the MediaWiki table instead
// 	$sql = "DELETE FROM $smw_specialprops WHERE property = " . $dbr->addQuotes(SMW_SP_INSTANCE_OF);
// 	$res = $dbr->query( $sql, $fname );
// 	// extend table structure for special properties
// 	$sql = "ALTER TABLE $smw_specialprops TYPE = innodb";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_specialprops ADD `subject_id` INT( 8 ) UNSIGNED NOT NULL FIRST";	
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_specialprops CHANGE `subjectns` `subject_namespace` INT( 11 ) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_specialprops CHANGE `subject` `subject_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_specialprops CHANGE `property` `property_id` SMALLINT NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_specialprops CHANGE `value` `value_string` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	
// 	
// 	// convert old values
// 	$sql = 'SELECT DISTINCT subject_title, subject_namespace FROM ' . $smw_specialprops;
// 	$res = $dbr->query( $sql, $fname );
// 	if($dbr->numRows( $res ) > 0) {
// 		$row = $dbr->fetchObject($res);
// 		while($row) {
// 			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
// 			if ($t != NULL) { 
// 				$id = $t->getArticleID(); 
// 				$stitle = $t->getDBkey();
// 				$sql = "UPDATE $smw_specialprops SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title);
// 				$dbr->query( $sql, $fname );
// 			}
// 			$row = $dbr->fetchObject($res);
// 		}
// 	}
// 	
// 	//add indices for new columns
// 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `subject_id` )";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `attribute_title` )";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_num` )";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_xsd` )";
// 	$dbr->query( $sql, $fname );	
// 	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `subject_id` )";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `relation_title` )";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `object_title` )";
// 	$dbr->query( $sql, $fname );		
// 	$sql = "ALTER TABLE $smw_specialprops ADD INDEX ( `subject_id` )";
// 	$dbr->query( $sql, $fname );
// 	
// // //We don't drop the tables -- some denormalization is good for our performance here ...
// 	// finally, drop the obsolete columns from all tables
// // 	$sql = "ALTER TABLE $smw_attributes DROP `subject_namespace`";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_attributes DROP `subject_title`";
// // 	$dbr->query( $sql, $fname );	
// // 	$sql = "ALTER TABLE $smw_relations DROP `subject_namespace`";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_relations DROP `subject_title`";
// // 	$dbr->query( $sql, $fname );	
// // 	$sql = "ALTER TABLE $smw_specialprops DROP `subject_namespace`";
// // 	$dbr->query( $sql, $fname );
// // 	$sql = "ALTER TABLE $smw_specialprops DROP `subject_title`";
// // 	$dbr->query( $sql, $fname );
// 	
// 	return "The database has been updated successfully.";
// }


