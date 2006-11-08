<?php
/**
 * @author S Page
 * @author Markus Kr�tzsch
 *
 * This special page for MediaWiki provides information about types.
 * Type information is  stored in the smw_attributes database table, 
 * some global variables managed by SMWTypeHandlerFactory,
 * and in Type: Wiki pages.
 * This only reports on the Type: Wiki pages.
 */


if (!defined('MEDIAWIKI')) die();

$wgExtensionFunctions[] = "wfSMWTypes";

function wfSMWTypes()
{
	global $wgMessageCache;
	smwfInitMessages(); // initialize messages, always called before anything else on this page
	global $IP, $smwgIP;
	require_once($smwgIP . '/includes/SMW_Storage.php');
	require_once( "$IP/includes/SpecialPage.php" );
	require_once( "$IP/includes/Title.php" );
	require_once("$IP/includes/QueryPage.php");

	class TypesPage extends QueryPage {
	
		function getName() {
			return "Types";
		}
	
		function isExpensive() {
			return false;
		}
	
		function isSyndicated() { return false; }
	
		function getPageHeader() {
			$text = '<p>' . wfMsg('smw_types_docu') . "</p><br />\n";
			// If on first page, do all the built-in types.
			if ($this->offset == 0) {
				$text .= $this->getBuiltins();
			}
			return $text;
		}

		/**
		 * Return HTML for the built-in types.
		 */
		function getBuiltins() {
			// Need skin to make links.  Should match skin passed to formatResult by parent QueryPage.
			global $wgUser;
			$sk = $wgUser->getSkin( );

			$text = '<h3>' . wfMsg('smw_types_builtin') . '</h3>';
			$text .= '<ol class="special">';	// Should match QueryPage.
			$typeLabels = SMWTypeHandlerFactory::getTypeLabels();
			sort($typeLabels);
			foreach ($typeLabels as $label) {
				$text .= '<li>' . $this->getTypeInfo( $label, $sk ) . '</li>';
			}
			$text .= '</ol>';
			return $text;
		}

		function getSQL() {
			$dbr =& wfGetDB( DB_SLAVE );
			$page = $dbr->tableName( 'page' );
			$NStype = SMW_NS_TYPE;
			// QueryPage uses the value from this SQL in an ORDER clause.
			// TODO: Perhaps use the dbr syntax from SpecialAllpages.
			return "SELECT 'Types' as type, 
						{$NStype} as namespace,
						page_title as title,
						page_title as value,
						1 as count
						FROM $page
						WHERE page_namespace = $NStype";
		}

		function sortDescending() {
			return false;
		}

		/**
		 * Returns the info about a type as HTML
		 */
		function getTypeInfo ($label, $skin) {
			$title = Title::makeTitle( SMW_NS_TYPE, $label );
			$link = $skin->makeLinkObj( $title, $title->getText() );

			// Unlike Attributes and Relations, we don't have a count and there's no URL to search by type.
			$text = $link;
			$extra = '';
			// Use the type handler interface to get more info.
			$th = SMWTypeHandlerFactory::getTypeHandlerByLabel($label);
			if ($th !== null) {
				$units = $th->getUnits();
				// TODO: String internationalization and localization.
				$stdunit = $units['STDUNIT'];
				$allunits = $units['ALLUNITS'];
				if (!is_array($allunits)) {
					$allunits = '';
				} else {
					$allunits = implode(", ", $allunits);
				}
				if ( strlen($stdunit) || strlen($allunits) ) {
					$extra = wfMsg('smw_types_units', $stdunit, $allunits);
				}
			}
			if (strlen($extra)) {
				$text .= "<br />&nbsp;&nbsp;&nbsp;$extra";
			}
			return $text;
			
		}
	
		function formatResult( $skin, $result ) {
			return $this->getTypeInfo($result->title, $skin);
		}

	}

	function doSpecialTypes($par = null)
	{
		list( $limit, $offset ) = wfCheckLimits();
		$rep = new TypesPage();
		return $rep->doQuery( $offset, $limit );
	}
	
	SpecialPage::addPage( new SpecialPage('Types','',true,'doSpecialTypes',false) );
}

/**
	OLD Technique for built-in types.
	function execute($par = null)
	{
		global $wgOut, $wgUser, $wgContLang;
		global $smwgTypeHandlersByLabel;
		
		$sk =& $wgUser->getSkin();

		$out = '<p>' . wfMsg('smw_types_docu') . "</p><br />\n";
		var_dump($smwgTypeHandlers)
		foreach ($smwgTypeHandlersByLabel as $name => $th) {
			// TODO: generate a link to each one.
			$out .= '<dt>' . $sk->makeLink($wgContLang->getNsText(SMW_NS_TYPE) . ':' . $name, $name);
			$out .= "</dt>\n<dd>";
			$units = $th->getUnits();
			// TODO: String internationalization and localization.
			$out .= 'Standard unit: ';
			$out .= strlen($units['STDUNIT']) ? $units['STDUNIT'] : '(none)';			
			$out .= '; supported units: ';
			if (is_array($units['ALLUNITS']) and sizeof($units['ALLUNITS']) ) {
				$out .= implode(", ", $units['ALLUNITS']);
			} else {
				$out .= 'N/A';
			}
			$out .= "</dd>\n";
		}
		// Could also query smw_attributes for value_datatype (and thus a count);
		// Could also query smw_specialprops for HAS_TYPE and value_string has Type.
*/

?>
