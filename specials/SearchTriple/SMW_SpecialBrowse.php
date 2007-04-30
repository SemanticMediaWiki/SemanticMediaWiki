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

function doSpecialBrowse($query = '') {
	SMW_SpecialBrowse::execute($query);
}

SpecialPage::addPage( new SpecialPage('Browse','',true,'doSpecialBrowse','default',true) );

/***
 * A class to encapsulate the special page that allows browsing through
 * the knowledge structure of a Semantic MediaWiki.
 */
class SMW_SpecialBrowse	 {

	static function execute($query = '') {
		global $wgRequest, $wgOut, $wgUser,$wgContLang, $smwgIQMaxLimit;
		$skin = $wgUser->getSkin();

		// get the GET parameters
		$articletext = $wgRequest->getVal( 'article' );
		// no GET parameters? Then try the URL
		if ('' == $articletext) { $articletext = $query; }
		$article = Title::newFromText( $articletext );
		$limit = $wgRequest->getVal( 'limit' );
		if ('' == $limit) $limit =  10;
		$offset = $wgRequest->getVal( 'offset' );
		if ('' == $offset) $offset = 0;
		$spectitle = Title::makeTitle( NS_SPECIAL, 'Browse' );
		$innerlimit = 4; // magic variable: how many linked articles should be shown?
		$html = '';

		$vsep = '<div class="smwhr"><hr /></div>';

		if ((NULL !== $article) && ('' !== $articletext)) { // legal article given
			$options = new SMWRequestOptions();
			$outrel = &smwfGetStore()->getOutRelations($article, $options);
			$atts = &smwfGetStore()->getAttributes($article, $options);
			$cats = &smwfGetStore()->getSpecialValues($article, SMW_SP_HAS_CATEGORY, $options);
			$redout = &smwfGetStore()->getSpecialValues($article, SMW_SP_REDIRECTS_TO, $options);
			$redin = &smwfGetStore()->getSpecialSubjects(SMW_SP_REDIRECTS_TO, $article, $options);
			$options->limit = $innerlimit;
			$instances = &smwfGetStore()->getSpecialSubjects(SMW_SP_HAS_CATEGORY, $article, $options);
			$options->limit = $limit+1;
			$options->offset = $offset;
			$options->sort = TRUE;
			// get results (get one more, to see if we have to add a link to more)
			$inrel = &smwfGetStore()->getInRelations($article, $options);

			$wgOut->setPagetitle($article->getFullText());

			$html .= '<table width="100%"><tr>';
			// left column (incoming links)
			$html .= '<td style="vertical-align=middle; text-align:right;" width="40%">';

			// prepare navigation bar
			if ($offset > 0)
				$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('Browse','offset=' . max(0,$offset-$limit) . '&article=' . urlencode($articletext) )) . '">' . wfMsg('smw_result_prev') . '</a>';
			else
				$navigation = wfMsg('smw_result_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp;'; // The following shows the numbers of the result.
			// Is this better, or not? TODO
			// <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + min(count($inrel), $limit)) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if (count($inrel)==($limit+1))
				$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('Browse', 'offset=' . ($offset+$limit) . '&article=' . urlencode($articletext) ))  . '">' . wfMsg('smw_result_next') . '</a>';
			else
				$navigation .= wfMsg('smw_result_next');

			if ((count($inrel) == 0) && (count($instances) == 0) && (count($redin)==0)) {
				$html .= '&nbsp;';
			} else {
				// no need to show the navigation bars when there is not enough to navigate
				if (($offset>0) || (count($inrel)>$limit)) $html .= $navigation;
				$html .= $vsep . "\n";
				if ((0==$offset) && (count($instances) > 0)) {
					$count = 0;
					foreach ($instances as $instance) {
						$count += 1;
						if ($count < $innerlimit) {
							$browselink = SMWInfolink::newBrowsinglink('+', $instance->getFulltext());
							$html .= $skin->makeKnownLinkObj( $instance ) . '&nbsp;' . $browselink->getHTML($skin);
							if ($count < count( $instances )) $html .= ', ';
						} else {
							$html .= $skin->makeKnownLinkObj( $article, wfMsg('smw_browse_more') );
						}
					}
					$html .= ' &nbsp;<strong>' . $skin->specialLink( 'Categories' ) . '</strong>';
					$html .= $vsep . "\n";
				}
				$count = count($redin);
				if ((0==$offset) && ($count > 0)) {
					foreach ($redin as $red) {
						$count -= 1;
						$browselink = SMWInfolink::newBrowsinglink('+', $red->getFulltext());
						$html .= $skin->makeKnownLinkObj( $red ) . '&nbsp;' . $browselink->getHTML($skin);
						if ($count > 0) $html .= ', ';
					}
					$html .= ' &nbsp;<strong>' . $skin->specialLink( 'Listredirects', 'isredirect' ) . '</strong>';
					$html .= $vsep . "\n";
				}
				foreach ($inrel as $result) {
					$subjectoptions = new SMWRequestOptions();
					$subjectoptions->limit = $innerlimit;
					$subjects = &smwfGetStore()->getRelationSubjects($result, $article, $subjectoptions);
					$subjectcount = count($subjects);
					$more = ($subjectcount == $innerlimit);
					$innercount = 0;
					foreach ($subjects as $subject) {
						$innercount += 1;
						if (($innercount < $innerlimit) || !$more) {
							$subjectlink = SMWInfolink::newBrowsingLink('+',$subject->getFullText());
							$html .= $skin->makeKnownLinkObj($subject, smwfT($subject, TRUE)) . '&nbsp;' . $subjectlink->getHTML($skin);
							if ($innercount<$subjectcount) $html .= ", \n";
						} else {
							$html .= '<a href="' . $skin->makeSpecialUrl('SearchByRelation', 'type=' . urlencode($result->getFullText()) . '&target=' . urlencode($article->getFullText())) . '">' . wfMsg("smw_browse_more") . "</a>\n";
						}
					}
					// replace the last two whitespaces in the relation name with
					// non-breaking spaces. Since there seems to be no PHP-replacer
					// for the last two, a strrev ist done twice to turn it around.
					// That's why nbsp is written backward.
					$html .= ' &nbsp;<strong>' . $skin->makeKnownLinkObj($result, strrev(preg_replace('/[\s]/', ';psbn&', strrev(smwfT($result)), 2) )) . '</strong>' . $vsep . "\n"; // TODO makeLinkObj or makeKnownLinkObj?
				}
				if (($offset>0) || (count($inrel)>$limit)) $html .= $navigation;
			}

			$html .= '</td><td style="vertical-align:middle; text-align:center;" width="18%">' . $skin->makeLinkObj($article, smwfT($article, TRUE)) . '</td><td style="vertical-align:middle; text-align:left;" width="40%">';

			if ((count($outrel) == 0) && (count($atts) == 0) && (count($cats) == 0) && (count($redout) == 0)) {
				$html .= '&nbsp;';
			} else {
				$html .= $vsep . "\n";
				foreach ($outrel as $result) {
					$objectoptions = new SMWRequestOptions();
					$objectoptions->limit = $innerlimit;
					$html .=  '<strong>' . $skin->makeKnownLinkObj($result, preg_replace('/[\s]/', '&nbsp;', smwfT($result), 2)) . "</strong>&nbsp; \n";// TODO makeLinkObj or makeKnownLinkObj?
					$objects = &smwfGetStore()->getRelationObjects($article, $result, $objectoptions);
					$objectcount = count($objects);
					$count = 0;
					foreach ($objects as $object) {
						$count += 1;
						if ($count == 4) {
							$querylink = SMWInfolink::newInverseRelationSearchLink( wfMsg("smw_browse_more"), $article->getPrefixedText(), $result->getText() );
							$html .= $querylink->getHTML($skin);
						} else {
							$searchlink = SMWInfolink::newBrowsingLink('+',$object->getFullText());
							$html .= $skin->makeLinkObj($object, smwfT($object, TRUE)) . '&nbsp;' . $searchlink->getHTML($skin);
						}
						if ($count<$objectcount) $html .= ", ";
					}
					$html .= $vsep."\n";
				}
				foreach ($atts as $att) {
					$objectoptions = new SMWRequestOptions();
					$html .=  '<strong>' . $skin->makeKnownLinkObj($att, preg_replace('/[\s]/', '&nbsp;', smwfT($att), 2)) . "</strong>&nbsp; \n";
					$objects = &smwfGetStore()->getAttributeValues($article, $att, $objectoptions);
					$objectcount = count($objects);
					$count = 0;
					foreach ($objects as $object) {
						$count += 1;
						$html .= $object->getValueDescription();
						if ($count<$objectcount) $html .= ", ";
					}
					$html .= $vsep."\n";
				}
				$count = count($redout);
				if ($count>0) {
					$html .= '<strong>' . $skin->specialLink( 'Listredirects', 'isredirect' ) . '</strong>&nbsp; ';
					foreach ($redout as $red) {
						$count -= 1;
						$browselink = SMWInfolink::newBrowsingLink('+', $red->getFullText());
						$html .= $skin->makeLinkObj($red, smwfT($red)) . '&nbsp;' . $browselink->getHTML($skin);
						if ($count > 0) $html .= ", ";
					}
					$html .= $vsep."\n";
				}
				$count = count($cats);
				if ($count>0) {
					$html .= '<strong>' . $skin->specialLink( 'Categories' ) . '</strong>&nbsp; ';
					$count = count($cats);
					foreach ($cats as $cat) {
						$count -= 1;
						$browselink = SMWInfolink::newBrowsingLink('+', $cat->getFullText());
						$html .= $skin->makeLinkObj($cat, smwfT($cat)) . '&nbsp;' . $browselink->getHTML($skin);
						if ($count > 0) $html .= ", ";
					}
					$html .= $vsep."\n";
				}
			}
			$html .= "</td></tr></table>";
			$html .= "<p>&nbsp;</p>";
		}

		// display query form
		$html .= '<form name="smwbrowse" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n";
		$html .= '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg('smw_browse_article') . "<br />\n";
		if (NULL == $article) {	$boxtext = $articletext; } else { $boxtext = $article->getFullText(); }
		$html .= '<input type="text" name="article" value="' . htmlspecialchars($boxtext) . '" />' . "\n";
		$html .= '<input type="submit" value="' . wfMsg('smw_browse_go') . "\"/>\n</form>\n";

		$wgOut->addHTML($html);
	}
}

///// Translation functions /////

/**
 * Shortcut to translateTitle with global language
 */
function smwfT(Title $title, $namespace = FALSE ) {
	global $wgLang;
	return smwfTranslateTitle($title, $wgLang, $namespace);
}

/**
 * Translate a title into another language, based on the langlinks-table.
 * This is just a first try, needs to be reworked into a proper translation
 * mechanism. Returns the Nameaspace.
 */
function smwfTranslateTitle(Title $title, Language $language, $namespace = FALSE ) {
	global $smwgTranslate;
	if ( !$smwgTranslate ) {
		if ( $namespace ) return $title->getFullText(); else return $title->getText();
	}
	$db =& wfGetDB( DB_MASTER ); // TODO: can we use SLAVE here? Is '=&' needed in PHP5?
	$sql = 'll_from=' . $db->addQuotes($title->getArticleID()) .
	       ' AND ll_lang=' . $db->addQuotes($language->mCode);
	$res = $db->select( $db->tableName('langlinks'),
	                    'll_title',
	                    $sql, 'SMW::translateTitle' );
	// return result as string (only the first -- there should be only one, anyway)
	if($db->numRows( $res ) > 0) {
		$row = $db->fetchObject($res);
		$db->freeResult($res);
		return $row->ll_title; // TODO need to get rid of NS in other language
	} else {
		$db->freeResult($res);
		if ( $namespace ) return $title->getFullText(); else return $title->getText();
	}
}

?>
