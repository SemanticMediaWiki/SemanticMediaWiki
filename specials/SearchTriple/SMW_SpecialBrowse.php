<?php
/**
 * @author Denny Vrandecic
 *
 * This special page for Semantic MediaWiki implements a
 * view on an article displaying outgoing and incoming
 * properties.
 */

if (!defined('MEDIAWIKI')) die();

global $IP, $smwgIP;
require_once( "$IP/includes/SpecialPage.php" );
require_once( "$smwgIP/includes/storage/SMW_Store.php" );


function doSpecialSMWBrowse($query = '') {
	SMW_SpecialBrowse::execute($query);
}

SpecialPage::addPage( new SpecialPage('SMWBrowse','',true,'doSpecialSMWBrowse',false) );



class SMW_SpecialBrowse	 {

	static function execute($query = '') {
		global $wgRequest, $wgOut, $wgUser, $smwgIQMaxLimit;
		$skin = $wgUser->getSkin();

		// get the GET parameters
		$articletext = $wgRequest->getVal( 'article' );
		// no GET parameters? Then try the URL
		if ('' == $articletext) {
			$articletext = $query;
		}
		$article = Title::newFromText( $articletext );
		$limit = $wgRequest->getVal( 'limit' );
		if ('' == $limit) $limit =  10;
		$offset = $wgRequest->getVal( 'offset' );
		if ('' == $offset) $offset = 0;
		$mode = $wgRequest->getVal( 'mode' );
		if (('' == $mode) || ('in' == $mode) || (wfMsg('smw_browse_in') == $mode)) { $mode = 'in'; } else { $mode = 'out'; }
		$html = '';
		$spectitle = Title::makeTitle( NS_SPECIAL, 'SMWBrowse' );

		// display query form
		$html .= '<form name="smwbrowse" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n";
		$html .= '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg('smw_browse_article') . "<br />\n";
		$html .= '<input type="submit" name="mode" value="' . wfMsg('smw_browse_in') .'"/>'."\n";
		$html .= '<input type="text" name="article" value="' . htmlspecialchars($articletext) . '" />' . "\n";
		$html .= '<input type="submit" name="mode" value="' . wfMsg('smw_browse_out') . "\"/>\n</form>\n";

		if ('' == $articletext) { // empty, no article name given
			$html .= wfMsg('smw_browse_docu') . "\n";
		} elseif ('in' == $mode) { // incoming links
			$options = new SMWRequestOptions();
			$options->limit = $limit+1;
			$options->offset = $offset;
			// get results (get one more, to see if we have to add a link to more)
			$results = &smwfGetStore()->getInRelations($article, $options);

			$html .= "<p>&nbsp;</p>\n" . wfMsg('smw_browse_displayresult', $skin->makeLinkObj($article)) . "<br />\n";

			// prepare navigation bar
			if ($offset > 0)
				$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SMWBrowse','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . '&article=' . urlencode($articletext) )) . '">' . wfMsg('smw_result_prev') . '</a>';
			else
				$navigation = wfMsg('smw_result_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + min(count($results), $limit)) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if (count($results)==($limit+1))
				$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('SMWBrowse', 'offset=' . ($offset+$limit) . '&limit=' . $limit . '&article=' . urlencode($articletext) ))  . '">' . wfMsg('smw_result_next') . '</a>';
			else
				$navigation .= wfMsg('smw_result_next');

			$max = false; $first=true;
			foreach (array(10,20,50,100,200) as $l) {
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
					$navigation .= '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SMWBrowse','offset=' . $offset . '&limit=' . $l . '&article=' . urlencode($articletext) )) . '">' . $l . '</a>';
				} else {
					$navigation .= '<b>' . $l . '</b>';
				}
			}
			$navigation .= ')';

			// no need to show the navigation bars when there is not enough to navigate
			if (($offset>0) || (count($results)>$limit))
				$html .= '<br />' . $navigation;
			if (count($results) == 0) {
				$html .= wfMsg( 'smw_browse_noin', $skin->makeSpecialUrl('SMWBrowse', 'article=' . urlencode($articletext) . '&mode=out' ));
			} else {
				$html .= '<table style="width: 100%; ">' . "\n";
				foreach ($results as $result) {
					$innerlimit = 6;
					$subjectoptions = new SMWRequestOptions();
					$subjectoptions->limit = $innerlimit;
					$html .= '<tr><td class="smwattname">' . "\n";
					$subjects = &smwfGetStore()->getRelationSubjects($result, $article, $subjectoptions);
					$more = (count($subjects) == $innerlimit);
					$innercount = 0;
					foreach ($subjects as $subject) {
						$innercount += 1;
						if (($innercount < $innerlimit) || !$more) {
							$html .= $skin->makeKnownLinkObj($subject) . ' <span class="smwsearch"><a href="' . $skin->makeSpecialUrl('SMWBrowse', 'article=' . urlencode($subject->getText())) . '">+</a></span>' . "<br />\n";
						} else {
							$html .= '<a href="' . $skin->makeSpecialUrl('SearchByRelation', 'type=' . urlencode($result->getText()) . '&target=' . urlencode($article->getText())) . '">' . wfMsg("smw_browse_more") . "</a><br />\n";
						}
					}
					$html .= '</td><td class="smwatts">' . $skin->makeLinkObj($result, $result->getText()) . " " . $skin->makeLinkObj($article) . "</td></tr>\n";
				}
				$html .= "</table>\n";
			}
			if (($offset>0) || (count($results)>$limit))
				$html .= $navigation;
		} else { // outgoing links
			$options = new SMWRequestOptions();
			$options->limit = $limit+1;
			$options->offset = $offset;
			// get results (get one more, to see if we have to add a link to more)
			$results = &smwfGetStore()->getOutRelations($article, $options);

			$html .= "<p>&nbsp;</p>\n" . wfMsg('smw_browse_displayout', $skin->makeLinkObj($article)) . "<br />\n";

			// prepare navigation bar
			if ($offset > 0)
				$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SMWBrowse','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . '&article=' . urlencode($articletext) )) . '">' . wfMsg('smw_result_prev') . '</a>';
			else
				$navigation = wfMsg('smw_result_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + min(count($results), $limit)) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if (count($results)==($limit+1))
				$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('SMWBrowse', 'offset=' . ($offset+$limit) . '&limit=' . $limit . '&article=' . urlencode($articletext) ))  . '">' . wfMsg('smw_result_next') . '</a>';
			else
				$navigation .= wfMsg('smw_result_next');

			$max = false; $first=true;
			foreach (array(10,20,50,100,200) as $l) {
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
					$navigation .= '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SMWBrowse','offset=' . $offset . '&limit=' . $l . '&article=' . urlencode($articletext) )) . '">' . $l . '</a>';
				} else {
					$navigation .= '<b>' . $l . '</b>';
				}
			}
			$navigation .= ')';

			// no need to show the navigation bars when there is not enough to navigate
			if (($offset>0) || (count($results)>$limit))
				$html .= '<br />' . $navigation;
			if (count($results) == 0) {
				$html .= wfMsg( 'smw_browse_noout', $skin->makeSpecialUrl('SMWBrowse', 'article=' . urlencode($articletext) . '&mode=in' ));
			} else {
				$html .= '<table style="width: 100%; ">' . "\n";
				foreach ($results as $result) {
					$objectoptions = new SMWRequestOptions();
					$html .= '<tr><td class="smwattname">' . "\n";
					$html .=  $skin->makeLinkObj($result, $result->getText()) . "\n";
					$html .= '</td><td class="smwatts">' . "\n";
					$objects = &smwfGetStore()->getRelationObjects($article, $result, $objectoptions);
					foreach ($objects as $object) {
						$html .= $skin->makeLinkObj($object) . ' <span class="smwsearch"><a href="' . $skin->makeSpecialUrl('SMWBrowse', 'article=' . urlencode($object->getText()) . '&mode=out') . '">+</a></span>' . "<br />\n";
					}
					$html .= "</td></tr>\n";
				}
				$html .= "</table>\n";
			}
			if (($offset>0) || (count($results)>$limit))
				$html .= $navigation;
		}

		$wgOut->addHTML($html);
	}

}
?>
