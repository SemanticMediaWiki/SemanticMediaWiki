<?php
/**
 * @author S Page
 * @author Markus Krötzsch
 *
 * This special page for MediaWiki provides information about types.
 * Type information is  stored in the smw_attributes database table, 
 * some global variables managed by SMWTypeHandlerFactory,
 * and in Type: Wiki pages.
 * This only reports on the Type: Wiki pages.
 */


if (!defined('MEDIAWIKI')) die();

global $IP;
include_once($IP . '/includes/QueryPage.php');

function smwfDoSpecialTypes() {
	wfProfileIn('smwfDoSpecialTypes (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/includes/SMW_DataValueFactory.php');
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new TypesPage();
	$result = $rep->doQuery( $offset, $limit );
	wfProfileOut('smwfDoSpecialTypes (SMW)');
	return $result;
}

class TypesPage extends QueryPage {

	function getName() {
		return "Types";
	}

	function isExpensive() {
		return false;
	}

	function isSyndicated() {
		return false; 
	}

	function getPageHeader() {
		return '<p>' . wfMsg('smw_types_docu') . "</p><br />\n";
	}

	function getSQL() {
		global $smwgContLang;
		$dbr =& wfGetDB( DB_SLAVE );
		$page = $dbr->tableName('page');
		$NStype = SMW_NS_TYPE;
		// TODO: Perhaps use the dbr syntax from SpecialAllpages.
		// NOTE: type, namespace, title and value must all be defined for QueryPage to work (incl. caching)
		$sql = "(SELECT 'Types' as type, {$NStype} as namespace, page_title as title, " .
		        "page_title as value, 1 as count FROM $page WHERE page_namespace = $NStype)";
		// make SQL for built-in datatypes
		foreach (SMWDataValueFactory::getKnownTypeLabels() as $label) {
			$label = str_replace(' ', '_', $label); // DBkey form so that SQL can elminate duplicates
			$sql .= " UNION (SELECT 'Types' as type,  {$NStype} as namespace, '$label' as title, " .
		            "'$label' as value, 1 as count)";
		}
		return $sql;
	}

	function sortDescending() {
		return false;
	}

	function formatResult( $skin, $result ) {
		return $this->getTypeInfo($skin, $result->value);
	}

	/**
	 * Returns the info about a type as HTML
	 */
	function getTypeInfo( $skin, $titletext ) {
		$title = Title::makeTitle( SMW_NS_TYPE, $titletext );

		// Use the type handler interface to get more info.
		$tv = SMWDataValueFactory::newTypeIDValue('__typ', $titletext);
		if ($tv->isBuiltIn() ) {
			$link = $skin->makeLinkObj( $title, $title->getText() );
		} else {
			$link = $skin->makeKnownLinkObj( $title, $title->getText() ); // page must exist
		}
// 		$units = $th->getUnits();
// 		// TODO: String internationalization and localization.
// 		$stdunit = $units['STDUNIT'];
// 		$allunits = $units['ALLUNITS'];
// 		if (!is_array($allunits)) {
// 			$allunits = '';
// 		} else {
// 			$allunits = implode(", ", $allunits);
// 		}
// 		if ( strlen($stdunit) || strlen($allunits) ) {
// 			$extra = wfMsg('smw_types_units', $stdunit, $allunits);
// 		}
// 
// 		if (strlen($extra)) {
// 			$text .= "<br />&nbsp;&nbsp;&nbsp;$extra";
// 		}
		return $link;
	}

}


