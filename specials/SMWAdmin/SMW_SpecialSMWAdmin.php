<?php
/**
 * @author Markus Krötzsch
 *
 * This special page for MediaWiki provides an administrative interface 
 * that allows to execute certain functions related to the maintainance 
 * of the semantic database. It is restricted to users with siteadmin status.
 */

if (!defined('MEDIAWIKI')) die();

$wgExtensionFunctions[] = "wfSMWAdminExtension";

function wfSMWAdminExtension()
{
	global $wgMessageCache;
	$wgMessageCache->addMessages(array('smwadmin' => 'Admin functions for Semantic MediaWiki'));
	
	SpecialPage::addPage( new SpecialPage('SMWAdmin','delete',true,'doSpecialSMWAdmin',false) );
}




function doSpecialSMWAdmin($par = null)
{
	global $IP, $smwgIP;
	require_once($smwgIP . '/includes/SMW_Storage.php');
	require_once( "$IP/includes/SpecialPage.php" );
	require_once( "$IP/includes/Title.php" );
	
	global $wgOut, $wgRequest;
	global $wgServer; // "http://www.yourserver.org"
						// (should be equal to 'http://'.$_SERVER['SERVER_NAME'])
	global $wgScript;   // "/subdirectory/of/wiki/index.php"
	global $wgUser;
	
	if ( ! $wgUser->isAllowed('delete') ) {
		$wgOut->sysopRequired();
		return;
	}
	
	/**** Execute actions if any ****/
	
	$action = $wgRequest->getText( 'action' );
	$message='';
	if ( $action=='updatetables' ) {
		$sure = $wgRequest->getText( 'udsure' );
		if ($sure == 'yes') {
			$message = smwfAdminUpdateTables();
		}
	}
	
// Special function for fixing development testing DBs after messing around ...
	if ( $action=='restore' ) {
		$message = smwfRestoreTableTitles();
	}
	
	/**** Output ****/
	
	$wgOut->setPageTitle(wfMsg('smwadmin'));
	
	// only report success/failure after an action
	if ( $message!='' ) {
		$html = $message;
		$html .= '<p> Return to <a href="' . $wgServer . $wgScript . '/Special:SMWAdmin">Special:SMWAdmin</p>';
		$wgOut->addHTML($html);
		return true;
	}
	
	// standard output interface
	$db =& wfGetDB( DB_MASTER );
	$curTableNames = $db->tableExists('smw_relations');
	if ($curTableNames === true) {
		$curFieldNames = $db->fieldExists('smw_relations', 'subject_id');
		$oldTableNames = false; // we assume this ...
	} else { 
		$oldTableNames = $db->tableExists('semantic_relations');
		$curFieldNames = false; 
	}
	
	
	$html = '<p>This special page helps you during installation and upgrade of 
				Semantic MediaWiki. Remember to backup valuable data before 
				executing administrative functions.</p>' . "\n";
	// creating tables and converting contents from older versions
	$html .= '<form name="buildtables" action="" method="POST">' . "\n" .
		'<input type="hidden" name="action" value="updatetables" />' . "\n";
	if (($curTableNames === true) && ($curFieldNames === false)) {
		$html .= '<h2>Upgrading from Semantic MediaWiki &le;0.3</h2>' . "\n" .
			'<p>Semantic MediaWiki versions until 0.3 used a slightly simpler internal data format. 
			The new format increases efficiency, but older entries must be 
			converted in order to be preserved. This does only affect your semantic 
			data; article texts are not touched.<p/>' . "\n";
		$html .= '<p>If the operation fails with obscure SQL errors, the database user employed 
			by your wiki (check your LocalSettings.php) probably does not have sufficient 
			permissions. Either grant this user additional persmissions to create and delete 
			tables, or temporarily enter the login of your database root in LocalSettings.php.<p/>' . 
			"\n" .
			'<input type="checkbox" name="udsure" value="yes">Yes, I am sure.</input>' .
			' &nbsp;&nbsp;&nbsp;<input type="submit" value="Update tables"/>' . "\n";
	} elseif (($curTableNames === false) && ($oldTableNames === false)) {
		$html .= '<h2>Preparing database for Semantic MediaWiki</h2>' . "\n" .
			'<p>Semantic MediaWiki requires some minor extensions to the MediaWiki database in 
			order to store the semantic data. These extensions must still be initialised on your
			site. The changes made in this step do not affect the rest of the MediaWiki database, 
			and can easily be undone if desired.<p/>' . "\n";
		$html .= '<p>If the operation fails with obscure SQL errors, the database user employed 
				by your wiki (check your LocalSettings.php) probably does not have sufficient 
				permissions. Either grant this user additional persmissions to create and delete 
				tables, or temporarily enter the login of your database root in LocalSettings.php.<p/>' .
				"\n" . '<input type="hidden" name="udsure" value="yes"/>' .
				'<input type="submit" value="Initialise tables"/>' . "\n";
	} elseif (($curTableNames === false) && ($oldTableNames === true)) {
		$html .= '<h2>Upgrade instructions from version &le;0.3</h2>' . "\n" .
			'<p>A installation of Semantic MediaWiki version &le; 0.3 was detected. To upgrade and convert existing semantic data where possible, subsequently install all released versions starting from your old version (e.g., if you used 0.2, download and install 0.3 next). Alternatively, you can manually delete the table <b>semantic_relations</b> from your wiki database. In this case, existing annotations will no longer be accessible until the respective articles are stored again.<p/>' . "\n";
	} else {
		$html .= '<h2>Semantic MediaWiki database status</h2>' . "\n" .
			'<p>Your database setup seems to be alright. If you experience problems with 
			your installation, check the guidelines in your INSTALL file. If this does not
			solve your problem, send an email to <a href="malito:semediawiki-user@lists.sourceforge.net">semediawiki-user@lists.sourceforge.net</a>.<p/>' . "\n";
	}
	$html .= '</form>';
	
	$wgOut->addHTML($html);
	return true;
}

// Special function for fixing development testing DBs after messing around ...
function smwfRestoreTableTitles() {
	$dbr =& wfGetDB( DB_SLAVE );
	extract( $dbr->tableNames( 'smw_attributes','smw_relations','smw_specialprops' ));
	
	// convert old values
	$sql = 'SELECT DISTINCT attribute_title FROM ' . $smw_attributes;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
			if ($t != NULL) { 
				$text = $t->getDBkey(); 
				$sql = "UPDATE $smw_attributes SET attribute_title = " . $dbr->addQuotes($text) . " WHERE attribute_title = " . $dbr->addQuotes($row->attribute_title) ;
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);			
		}
	}	
	
	// convert old values
	$sql = 'SELECT DISTINCT relation_title FROM ' . $smw_relations;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->relation_title, SMW_NS_RELATION);
			if ($t != NULL) { 
				$text = $t->getDBkey(); 
				$sql = "UPDATE $smw_relations SET relation_title = " . $dbr->addQuotes($text) . " WHERE relation_title = " . $dbr->addQuotes($row->relation_title) ;
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);			
		}
	}
	
	// convert old values
	$sql = 'SELECT DISTINCT subject_title, subject_namespace FROM ' . $smw_attributes;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
			if ($t != NULL) { 
				$id = $t->getArticleID(); 
				$stitle = $t->getDBkey(); 
				$sql = "UPDATE $smw_attributes SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title);
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);			
		}
	}	
	
	// convert old values
	$sql = 'SELECT DISTINCT subject_title, subject_namespace, object_title FROM ' . $smw_relations;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
			if ($t != NULL) { 
				$id = $t->getArticleID(); 
				$stitle = $t->getDBkey(); 
				$sql = "UPDATE $smw_relations SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . ", object_title = " . $dbr->addQuotes(str_replace(" ", "_", $row->object_title)) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title) . " AND object_title = " . $dbr->addQuotes($row->object_title);
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);
		}
	}	
	
	// convert old values
	$sql = 'SELECT DISTINCT subject_title, subject_namespace FROM ' . $smw_specialprops;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
			if ($t != NULL) { 
				$id = $t->getArticleID(); 
				$stitle = $t->getDBkey();
				$sql = "UPDATE $smw_specialprops SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title);
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);
		}
	}
	
	//add more indices for new columns
// 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `attribute_title` )";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_num` )";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_xsd` )";
// 	$dbr->query( $sql, $fname );	
// 	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `relation_title` )";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `object_title` )";
// 	$dbr->query( $sql, $fname );
	
	
// 	// modify table structure for attributes
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `subject_title` `subject_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `attribute_title` `attribute_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `value_unit` `value_unit` VARCHAR(63)";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `value_datatype` `value_datatype` VARCHAR(31) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes CHANGE `value_xsd` `value_xsd` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	
// 	$sql = "ALTER TABLE $smw_relations CHANGE `subject_title` `subject_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations CHANGE `relation_title` `relation_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations CHANGE `object_title` `object_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );	
// 	
// 	$sql = "ALTER TABLE $smw_specialprops CHANGE `subject_title` `subject_title` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_specialprops CHANGE `value_string` `value_string` VARCHAR(255) NOT NULL";
// 	$dbr->query( $sql, $fname );
	
	return "Done";
}

/**
 * A function for updating tables from 0.3 to post 0.3
 */
function smwfAdminUpdateTables() {
	$dbr =& wfGetDB( DB_SLAVE );

	if ($dbr->tableExists('smw_relations') === false) {
		smwfMakeSemanticTables();
		return 'The database has been initialised successfully.';
	}

	if ($dbr->fieldExists('smw_relations', 'subject_id')) {
		return 'This function was probably called accidentally. Your database already has the required structure.';
	}
	
	extract( $dbr->tableNames( 'smw_attributes','smw_relations','smw_specialprops' ));
	$fname = 'SMW::AdminUpdateTables';
	
	// modify table structure for attributes
	$sql = "ALTER TABLE $smw_attributes TYPE = innodb";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes ADD `subject_id` INT( 8 ) UNSIGNED NOT NULL FIRST";	
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes CHANGE `subjectns` `subject_namespace` INT( 11 ) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes CHANGE `subject` `subject_title` VARCHAR(255) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes CHANGE `attribute` `attribute_title` VARCHAR(255) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes CHANGE `unit` `value_unit` VARCHAR(63)";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes CHANGE `datatype` `value_datatype` VARCHAR(31) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes CHANGE `value` `value_xsd` VARCHAR(255) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes ADD `value_num` DOUBLE";
	$dbr->query( $sql, $fname );
	
	// convert old values
	$sql = 'SELECT DISTINCT subject_title, subject_namespace FROM ' . $smw_attributes;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
			if ($t != NULL) { 
				$id = $t->getArticleID(); 
				$stitle = $t->getDBkey(); 
				$sql = "UPDATE $smw_attributes SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title);
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);			
		}
	}
	
	// create new scalar representations
	// (luckily, for all 0.3 datatypes that have a scalar representation, the
	// scalar version has the same PHP representation as the textual one. 
	foreach (array('int','float','geoarea','geolength') as $type) {
		$sql = "SELECT DISTINCT value_xsd FROM $smw_attributes WHERE value_datatype = " . $dbr->addQuotes($type);
		$res = $dbr->query( $sql, $fname );
		if($dbr->numRows( $res ) > 0) {
			$row = $dbr->fetchObject($res);
			while($row) {
				$sql = "UPDATE $smw_attributes SET value_num = $row->value_xsd WHERE value_xsd = $row->value_xsd AND value_datatype = " . $dbr->addQuotes($type);
				$dbr->query( $sql, $fname );
				
				$row = $dbr->fetchObject($res);
			}
		}
	}
	
	// rename attributes according to their DBkey
	$sql = 'SELECT DISTINCT attribute_title FROM ' . $smw_attributes;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->attribute_title, SMW_NS_ATTRIBUTE);
			if ($t != NULL) { 
				$text = $t->getDBkey(); 
				$sql = "UPDATE $smw_attributes SET attribute_title = " . $dbr->addQuotes($text) . " WHERE attribute_title = " . $dbr->addQuotes($row->attribute_title) ;
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);
		}
	}
	
	// modify table structure for relations
	$sql = "ALTER TABLE $smw_relations TYPE = innodb";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_relations ADD `subject_id` INT( 8 ) UNSIGNED NOT NULL FIRST";	
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_relations CHANGE `subjectns` `subject_namespace` INT( 11 ) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_relations CHANGE `subject` `subject_title` VARCHAR(255) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_relations CHANGE `relation` `relation_title` VARCHAR(255) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_relations CHANGE `objectns` `object_namespace` INT( 11 ) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_relations CHANGE `object` `object_title` VARCHAR(255) NOT NULL";
	$dbr->query( $sql, $fname );
	
	// convert old values
	$sql = 'SELECT DISTINCT subject_title, subject_namespace, object_title FROM ' . $smw_relations;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
			if ($t != NULL) { 
				$id = $t->getArticleID(); 
				$stitle = $t->getDBkey(); 
				$sql = "UPDATE $smw_relations SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . ", object_title = " . $dbr->addQuotes(str_replace(" ", "_", $row->object_title)) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title) . " AND object_title = " . $dbr->addQuotes($row->object_title);
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);
		}
	}
	
	// convert old values
	$sql = 'SELECT DISTINCT relation_title FROM ' . $smw_relations;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->relation_title, SMW_NS_RELATION);
			if ($t != NULL) { 
				$text = $t->getDBkey(); 
				$sql = "UPDATE $smw_relations SET relation_title = " . $dbr->addQuotes($text) . " WHERE relation_title = " . $dbr->addQuotes($row->relation_title) ;
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);	
		}
	}
	
	
	// delete obsolete categorisation values; in the future, we will use the MediaWiki table instead
	$sql = "DELETE FROM $smw_specialprops WHERE property = " . $dbr->addQuotes(SMW_SP_HAS_CATEGORY);
	$res = $dbr->query( $sql, $fname );
	// extend table structure for special properties
	$sql = "ALTER TABLE $smw_specialprops TYPE = innodb";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_specialprops ADD `subject_id` INT( 8 ) UNSIGNED NOT NULL FIRST";	
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_specialprops CHANGE `subjectns` `subject_namespace` INT( 11 ) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_specialprops CHANGE `subject` `subject_title` VARCHAR(255) NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_specialprops CHANGE `property` `property_id` SMALLINT NOT NULL";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_specialprops CHANGE `value` `value_string` VARCHAR(255) NOT NULL";
	$dbr->query( $sql, $fname );
	
	
	// convert old values
	$sql = 'SELECT DISTINCT subject_title, subject_namespace FROM ' . $smw_specialprops;
	$res = $dbr->query( $sql, $fname );
	if($dbr->numRows( $res ) > 0) {
		$row = $dbr->fetchObject($res);
		while($row) {
			$t = Title::newFromText($row->subject_title,$row->subject_namespace);
			if ($t != NULL) { 
				$id = $t->getArticleID(); 
				$stitle = $t->getDBkey();
				$sql = "UPDATE $smw_specialprops SET subject_id = $id, subject_title = " . $dbr->addQuotes($stitle) . " WHERE subject_namespace = $row->subject_namespace AND subject_title = " . $dbr->addQuotes($row->subject_title);
				$dbr->query( $sql, $fname );
			}
			$row = $dbr->fetchObject($res);
		}
	}
	
	//add indices for new columns
	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `subject_id` )";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `attribute_title` )";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_num` )";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_attributes ADD INDEX ( `value_xsd` )";
	$dbr->query( $sql, $fname );	
	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `subject_id` )";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `relation_title` )";
	$dbr->query( $sql, $fname );
	$sql = "ALTER TABLE $smw_relations ADD INDEX ( `object_title` )";
	$dbr->query( $sql, $fname );		
	$sql = "ALTER TABLE $smw_specialprops ADD INDEX ( `subject_id` )";
	$dbr->query( $sql, $fname );
	
// //We don't drop the tables -- some denormalization is good for our performance here ...
	// finally, drop the obsolete columns from all tables
// 	$sql = "ALTER TABLE $smw_attributes DROP `subject_namespace`";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_attributes DROP `subject_title`";
// 	$dbr->query( $sql, $fname );	
// 	$sql = "ALTER TABLE $smw_relations DROP `subject_namespace`";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_relations DROP `subject_title`";
// 	$dbr->query( $sql, $fname );	
// 	$sql = "ALTER TABLE $smw_specialprops DROP `subject_namespace`";
// 	$dbr->query( $sql, $fname );
// 	$sql = "ALTER TABLE $smw_specialprops DROP `subject_title`";
// 	$dbr->query( $sql, $fname );
	
	return "The database has been updated successfully.";
}

?>
