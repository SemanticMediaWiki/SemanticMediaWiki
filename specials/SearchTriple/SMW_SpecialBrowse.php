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
		if (('' == $mode) || ('out' == $mode)) { $mode = 'out'; } else { $mode = 'in'; }
		$html = '';
		$spectitle = Title::makeTitle( NS_SPECIAL, 'SMWBrowse' );

		// display query form
		$html .= '<form name="smwbrowse" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n";
		$html .= '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg('smw_browse_article') . "<br />\n";
		if (NULL == $article) {	$boxtext = $articletext; } else { $boxtext = $article->getFullText(); }
		$html .= '<input type="text" name="article" value="' . htmlspecialchars($boxtext) . '" />' . "\n";
		$html .= '<input type="submit" value="' . wfMsg('smw_browse_go') . "\"/>\n</form>\n";

		$vsep = '<tr><td colspan="2"><div class="smwhr"><hr /></div></td></tr>';

		if (('' == $articletext) || (NULL == $article)) { // empty, no article name given
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
				$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SMWBrowse','offset=' . max(0,$offset-$limit) . '&article=' . urlencode($articletext) )) . '&mode=in">' . wfMsg('smw_result_prev') . '</a>';
			else
				$navigation = wfMsg('smw_result_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + min(count($results), $limit)) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if (count($results)==($limit+1))
				$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('SMWBrowse', 'offset=' . ($offset+$limit) . '&article=' . urlencode($articletext) ))  . '&mode=in">' . wfMsg('smw_result_next') . '</a>';
			else
				$navigation .= wfMsg('smw_result_next');

			if (count($results) == 0) {
				$html .= wfMsg( 'smw_browse_noin', $skin->makeSpecialUrl('SMWBrowse', 'article=' . urlencode($articletext) . '&mode=out' ));
			} else {
				$html .= 'See all <a href="' . $skin->makeSpecialUrl('SMWBrowse', 'mode=out&article=' . urlencode($articletext)) . '">outgoing links of ' . $article->getText() .  "</a><br /><br />\n"; // TODO
				// no need to show the navigation bars when there is not enough to navigate
				if (($offset>0) || (count($results)>$limit))
					$html .= $navigation;
				$html .= '<table style="width: 100%; ">' . $vsep . "\n";
				foreach ($results as $result) {
					$innerlimit = 6;
					$subjectoptions = new SMWRequestOptions();
					$subjectoptions->limit = $innerlimit;
					$html .= '<tr><td class="smwsubjects">' . "\n";
					$subjects = &smwfGetStore()->getRelationSubjects($result, $article, $subjectoptions);
					$subjectcount = count($subjects);
					$more = ($subjectcount == $innerlimit);
					$innercount = 0;
					foreach ($subjects as $subject) {
						$innercount += 1;
						if (($innercount < $innerlimit) || !$more) {
							$subjectlink = SMWInfolink::newBrowsingLink('+',$subject->getFullText(), FALSE);
							$html .= $skin->makeKnownLinkObj($subject) . '&nbsp;&nbsp;' . $subjectlink->getHTML($skin);
							if ($innercount<$subjectcount) $html .= ", \n";
						} else {
							$html .= '<a href="' . $skin->makeSpecialUrl('SearchByRelation', 'type=' . urlencode($result->getFullText()) . '&target=' . urlencode($article->getFullText())) . '">' . wfMsg("smw_browse_more") . "</a><br />\n";
						}
					}
					$html .= '</td><td class="smwrelright">' . $skin->makeLinkObj($result, $result->getText()) . " " . $article->getFullText() . '</td></tr>' . $vsep . "\n";
				}
				$html .= "</table>\n";
			}
			if (($offset>0) || (count($results)>$limit))
				$html .= $navigation;
		} else { // outgoing links
			$options = new SMWRequestOptions();
			$results = &smwfGetStore()->getOutRelations($article, $options);
			$atts = &smwfGetStore()->getAttributes($article, $options);

			$html .= "<p>&nbsp;</p>\n" . wfMsg('smw_browse_displayout', $skin->makeLinkObj($article)) . "<br />\n";

			if ((count($results) == 0) && (count($atts) == 0)) {
				$html .= wfMsg( 'smw_browse_noout', $skin->makeSpecialUrl('SMWBrowse', 'article=' . urlencode($articletext) . '&mode=in' ));
			} else {
				$html .= 'See all <a href="' . $skin->makeSpecialUrl('SMWBrowse', 'mode=in&article=' . urlencode($articletext)) . '">incoming links of ' . $article->getFullText() .  "</a><br /><br />\n"; // TODO
				$html .= '<table style="width: 100%; ">'. $vsep . "\n";
				foreach ($results as $result) {
					$objectoptions = new SMWRequestOptions();
					$html .= '<tr><td class="smwattname">' . "\n";
					$html .=  $skin->makeLinkObj($result, $result->getText()) . "\n";
					$html .= '</td><td class="smwatts">' . "\n";
					$objects = &smwfGetStore()->getRelationObjects($article, $result, $objectoptions);
					$objectcount = count($objects);
					$count = 0;
					foreach ($objects as $object) {
						$count += 1;
						$searchlink = SMWInfolink::newBrowsingLink('+',$object->getFullText());
						$html .= $skin->makeLinkObj($object) . '&nbsp;&nbsp;' . $searchlink->getHTML($skin);
						if ($count<$objectcount) $html .= ", ";
					}
					$html .= '</td></tr>'.$vsep."\n";
				}
				foreach ($atts as $att) {
					$objectoptions = new SMWRequestOptions();
					$html .= '<tr><td class="smwattname">' . "\n";
					$html .=  $skin->makeKnownLinkObj($att, $att->getText()) . "\n";
					$html .= '</td><td class="smwatts">' . "\n";
					$objects = &smwfGetStore()->getAttributeValues($article, $att, $objectoptions);
					$objectcount = count($objects);
					$count = 0;
					foreach ($objects as $object) {
						$count += 1;
						$html .= $object->getValueDescription();
						if ($count<$objectcount) $html .= ", ";
					}
					$html .= '</td></tr>'.$vsep."\n";
				}
				$html .= "</table>\n";
			}
		}

		$wgOut->addHTML($html);
	}

}
?>
