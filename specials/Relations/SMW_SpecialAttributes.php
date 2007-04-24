<?php
/**
 * @author Denny Vrandecic
 *
 * This page shows all used attributes.
 */

if (!defined('MEDIAWIKI')) die();

global $IP;
require_once( "$IP/includes/SpecialPage.php" );
require_once( "$IP/includes/Title.php" );
require_once("$IP/includes/QueryPage.php");

function doSpecialAttributes($par = null) {
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new AttributesPage();
	return $rep->doQuery( $offset, $limit );
}

SpecialPage::addPage( new SpecialPage('Attributes','',true,'doSpecialAttributes',false) );


class AttributesPage extends QueryPage {

	function getName() {
		return "Attributes";
	}

	function isExpensive() {
		return false;
	}

	function isSyndicated() { return false; }

	function getPageHeader() {
		return '<p>' . wfMsg('smw_attributes_docu') . "</p><br />\n";
	}
	function getSQL() {
		$NSatt = SMW_NS_ATTRIBUTE;
		$dbr =& wfGetDB( DB_SLAVE );
		$attributes = $dbr->tableName( 'smw_attributes' );
		// QueryPage uses the value from this SQL in an ORDER clause,
		// so return attribute title in value, and its type in title.
		return "SELECT 'Attributes' as type, 
					{$NSatt} as namespace,
					value_datatype as title,
					attribute_title as value,
					COUNT(*) as count
					FROM $attributes
					GROUP BY attribute_title, value_datatype";
	}
	
	function sortDescending() {
		return false;
	}

	function formatResult( $skin, $result ) {
		global $wgLang, $wgExtraNamespaces;
		// The attribute title is in value, see getSQL().
		$attrtitle = Title::makeTitle( SMW_NS_ATTRIBUTE, $result->value );
		$attrlink = $skin->makeLinkObj( $attrtitle, $attrtitle->getText() );
		// The value_datatype is in title, see getSQL().
		
		$typelabel = SMWTypeHandlerFactory::getTypeLabelByID($result->title);
		if ( $typelabel === NULL ) { // type unknown, maybe some upgrade problem
			$typelabel = $result->title;
		}
		$typetitle = Title::makeTitle( SMW_NS_TYPE, $typelabel );
		$typelink = $skin->makeLinkObj( $typetitle);

		return "$attrlink ($result->count)" . wfMsg('smw_attr_type_join', $typelink);
	}
}

?>