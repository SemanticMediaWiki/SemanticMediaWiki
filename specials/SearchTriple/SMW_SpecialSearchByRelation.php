<?php
/**
 * @author Denny Vrandecic
 *
 * This special page for Semantic MediaWiki implements a
 * view on a relation-object pair, i.e. a typed baclink.
 * For example, it shows me all persons born in Croatia,
 * or all winners of the Academy Award for best actress.
 */

if (!defined('MEDIAWIKI')) die();

global $IP, $smwgIP;
require_once( "$IP/includes/SpecialPage.php" );
require_once( "$smwgIP/includes/storage/SMW_Store.php" );


function doSpecialSearchByRelation($query = '') {
	SMW_SearchByRelation::execute($query);
}

SpecialPage::addPage( new SpecialPage('SearchByRelation','',true,'doSpecialSearchByRelation',false) );



class SMW_SearchByRelation {

	static function execute($query = '') {
		global $wgRequest, $wgOut, $wgUser, $smwgIQMaxLimit;
		$skin = $wgUser->getSkin();
		
		// get the GET parameters
		$type = $wgRequest->getVal( 'type' );
		$target = $wgRequest->getVal( 'target' );
		// no GET parameters? Then try the URL
		if (('' == $type) && ('' == $target)) {
			$queryparts = explode('::', $query);
			$type = $query;
			if (count($queryparts) > 1) {
				$type = $queryparts[0];
				$target = implode('::', array_slice($queryparts, 1));
			}
		}
		$relation = Title::newFromText( $type, SMW_NS_RELATION );
		if (NULL != $relation) { $type = $relation->getText(); } else { $type = ''; }
		$object = Title::newFromText( $target );
		if (NULL != $object) { $target = $object->getText(); } else { $target = ''; }
		$limit = $wgRequest->getVal( 'limit' );
		if ('' == $limit) $limit =  20;
		$offset = $wgRequest->getVal( 'offset' );
		if ('' == $offset) $offset = 0;
		$html = '';
		$spectitle = Title::makeTitle( NS_SPECIAL, 'SearchByRelation' );

		if (('' == $type) && ('' == $target)) { // empty page. No relation and no object given.
			$html .= wfMsg('smw_tb_docu') . "\n";
		} elseif ('' == $type) { // no relation given
			$wlhtitle = Title::makeTitle( NS_SPECIAL, 'Whatlinkshere' );
			$wlhlink = htmlspecialchars($skin->makeSpecialUrl('Whatlinkshere/' . $object->getPrefixedURL()));
			$html .= wfMsg('smw_tb_notype', $object->getPrefixedText(), $wlhlink);
		} elseif ('' == $target) { // no object given
			$html .= wfMSG('smw_tb_notarget', $skin->makeLinkObj($relation, $relation->getText()));
		} else { // everything is given
			$wgOut->setPagetitle($relation->getText() . ' ' . $object->getFullText());
			$options = new SMWRequestOptions();
			$options->limit = $limit+1;
			$options->offset = $offset;
			// get results (get one more, to see if we have to add a link to more)
			$results = &smwfGetStore()->getRelationSubjects($relation, $object, $options);

			$html .= wfMsg('smw_tb_displayresult', $skin->makeLinkObj($relation, $relation->getText()), $skin->makeLinkObj($object)) . "<br />\n";

			// prepare navigation bar
			if ($offset > 0)
				$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SearchByRelation','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . '&type=' . urlencode($type) .'&target=' . urlencode($target))) . '">' . wfMsg('smw_result_prev') . '</a>';
			else
				$navigation = wfMsg('smw_result_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + min(count($results), $limit)) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if (count($results)==($limit+1))
				$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('SearchByRelation', 'offset=' . ($offset+$limit) . '&limit=' . $limit . '&type=' . urlencode($type) . '&target=' . urlencode($target)))  . '">' . wfMsg('smw_result_next') . '</a>';
			else
				$navigation .= wfMsg('smw_result_next');

			$max = false; $first=true;
			foreach (array(20,50,100,250,500) as $l) {
				if ($max) continue;
				if ($first) {
					$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(';
					$first = false;
				} else
					$navigation .= ' | ';
				if ($l > $smwgIQMaxLimit) {
					$l = $smwgIQMaxLimit;
					$max = true;
				}
				if ( $limit != $l ) {
					$navigation .= '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SearchByRelation','offset=' . $offset . '&limit=' . $l . '&type=' . urlencode($type) . '&target=' . urlencode($target))) . '">' . $l . '</a>';
				} else {
					$navigation .= '<b>' . $l . '</b>';
				}
			}
			$navigation .= ')';

			// no need to show the navigation bars when there is not enough to navigate
			if (($offset>0) || (count($results)>$limit))
				$html .= '<br />' . $navigation;
			if (count($results) == 0)
				$html .= wfMsg( 'smw_result_noresults' );
			else {
				$html .= "<ul>\n";
				foreach ($results as $result) {
					$browselink = SMWInfolink::newBrowsingLink('+',$result->getPrefixedText());
					$html .= '<li>' . $skin->makeKnownLinkObj($result) . '&nbsp;&nbsp;' . $browselink->getHTML($skin) . "</li> \n";
				}
				$html .= "</ul>\n";
			}
			if (($offset>0) || (count($results)>$limit))
				$html .= $navigation;
		}

		// display query form
		$html .= '<p>&nbsp;</p>';
		$html .= '<form name="searchbyrelation" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
		         '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg('smw_tb_linktype') . ' <input type="text" name="type" value="' . htmlspecialchars($type) . '" />' . "&nbsp;&nbsp;&nbsp;\n";
		$html .= wfMsg('smw_tb_linktarget') . ' <input type="text" name="target" value="' . htmlspecialchars($target) . '" />' . "\n";
		$html .= '<input type="submit" value="' . wfMsg('smw_tb_submit') . "\"/>\n</form>\n";

		$wgOut->addHTML($html);
	}

}
?>
