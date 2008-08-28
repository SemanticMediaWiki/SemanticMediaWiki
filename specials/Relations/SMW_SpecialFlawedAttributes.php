<?php
/**
 * @author Daniel M. Herzig
 *
 * @version 0.5
 *
 * This page shows all flawed attributes ("Oops").
 * The idea is that every correct attribute is stored in the smw_attributes table.
 * However, every attribute regardless its correctness is stored in the table pagelinks with namespace = 102.
 * Thus, the query below returns all pages, which have a pagelink with namespace 102 but no corresponding entry in the smw_attributes table.
 *
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */

function doSpecialFlawedAttributes($par = null) {
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new FlawedAttributes();
	return $rep->doQuery( $offset, $limit );
}

SpecialPage::addPage( new SpecialPage('FlawedAttributes','',true,'doSpecialFlawedAttributes',false) );


class FlawedAttributes extends QueryPage {

	function getName() {
		return "Flawed Attributes";
	}

	function isExpensive() {
		return false;
	}

	function isSyndicated() { return false; }

	function getPageHeader() {
		wfLoadExtensionMessages('SemanticMediaWiki');
		return '<p>' . wfMsg('smw_fattributes') . "</p><br />\n";
	}

	function getSQL() {
		$dbr =& wfGetDB( DB_SLAVE );
		$page = $dbr->tableName( 'page');
		$pagelinks = $dbr->tableName( 'pagelinks');
		$smw_attributes = $dbr->tableName( 'smw_attributes' );
		$NSmain = NS_MAIN;
		$NSatt = SMW_NS_ATTRIBUTE; /*\"102\"*/

		return
				"SELECT 'FlawedAttributes' as type,
				{$NSmain} as namespace,
				page_title as title,
				page_title as value,
				COUNT(*) as count
				FROM $page
				INNER JOIN $pagelinks
				ON $page.page_id = $pagelinks.pl_from
				WHERE
					pl_namespace = {$NSatt}
					AND
					(page_id, pl_title) NOT IN
					(SELECT subject_id, attribute_title
						from $smw_attributes)
				GROUP BY page_id
				";



	}

	function sortDescending() {
		return false;
	}

	function formatResult( $skin, $result ) {
		global $wgLang;
		$title = Title::makeTitle( NS_MAIN, $result->title );
		$link = $skin->makeLinkObj( $title, $title->getText() );
		return "$link ($result->count)";
	}
}
