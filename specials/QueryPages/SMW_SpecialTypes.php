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
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */

function smwfDoSpecialTypes() {
	global $wgOut;
	wfProfileIn('smwfDoSpecialTypes (SMW)');
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new TypesPage();
	$result = $rep->doQuery( $offset, $limit );
	SMWOutputs::commitToOutputPage($wgOut); // make sure locally collected output data is pushed to the output!
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
		wfLoadExtensionMessages('SemanticMediaWiki');
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
		        "page_title as value, 1 as count FROM $page WHERE page_namespace = $NStype AND page_is_redirect = '0')";
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
		$tv = SMWDataValueFactory::newTypeIDValue('__typ', $titletext);
		$info = array();
		$error = array();
		wfLoadExtensionMessages('SemanticMediaWiki');
		if ($tv->isAlias()) { // print the type title as found, long text would (again) print the alias
			$ttitle = Title::makeTitle(SMW_NS_TYPE, $titletext);
			$link = $skin->makeKnownLinkObj($ttitle, $ttitle->getText()); // aliases are only found if the page exists
			$info[] = wfMsg('smw_isaliastype', $tv->getLongHTMLText());
		} else {
			$link = $tv->getLongHTMLText($skin);
			if (!$tv->isBuiltIn()) { // find out whether and how this was user-defined
				$dv = SMWDataValueFactory::newTypeObjectValue($tv);
				$units = $dv->getUnitList();
				if (count($units)==0) {
					$error[] = wfMsg('smw_isnotype', $tv->getLongHTMLText());
				} else {
					$info[] = wfMsg('smw_typeunits', $tv->getLongHTMLText(), implode(', ', $units));
				}
			}
		}
	
		if (count($error)>0) {
			$link .= smwfEncodeMessages($error);
		}
		if (count($info)>0) {
			$link .= smwfEncodeMessages($info,'info');
		}
		return $link;
	}

}


