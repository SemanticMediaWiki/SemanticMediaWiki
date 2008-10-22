<?php
/**
 * @file
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * Special page to show object relation pairs.
 *
 * @author Denny Vrandecic
 */

/**
 * This special page for Semantic MediaWiki implements a
 * view on a object-relation pair, i.e. a page that shows
 * all the fillers of a property for a certain page.
 * This is typically used for overflow results from other 
 * dynamic output pages.
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 */
class SMWPageProperty extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('PageProperty', '', false);
		wfLoadExtensionMessages('SemanticMediaWiki');
	}

	public function execute($query = '') {
		global $wgRequest, $wgOut, $wgUser;

		$skin = $wgUser->getSkin();
		$this->setHeaders();

		// get the GET parameters
		$from = $wgRequest->getVal( 'from' );
		$type = $wgRequest->getVal( 'type' );
		// no GET parameters? Then try the URL
		if (('' == $type) && ('' == $from)) {
			$queryparts = explode('::', $query);
			$type = $query;
			if (count($queryparts) > 1) {
				$from = $queryparts[0];
				$type = implode('::', array_slice($queryparts, 1));
			}
		}
		$subject = Title::newFromText( $from );
		if (NULL != $subject) { $from = $subject->getText(); } else { $from = ''; }
		$property = SMWPropertyValue::makeUserProperty($type);
		if ($property->isvalid()) {
			$type = $property->getWikiValue();
		} else {
			$type = '';
		}

		$limit = $wgRequest->getVal( 'limit' );
		if ('' == $limit) $limit =  20;
		$offset = $wgRequest->getVal( 'offset' );
		if ('' == $offset) $offset = 0;
		$html = '';
		$spectitle = Title::makeTitle( NS_SPECIAL, 'PageProperty' );

		wfLoadExtensionMessages('SemanticMediaWiki');

		if (('' == $type) || ('' == $from)) { // No relation or subject given.
			$html .= wfMsg('smw_pp_docu') . "\n";
		} else { // everything is given
			$wgOut->setPagetitle($subject->getFullText() . ' ' . $property->getWikiValue());
			$options = new SMWRequestOptions();
			$options->limit = $limit+1;
			$options->offset = $offset;
			$options->sort = true;
			// get results (get one more, to see if we have to add a link to more)
			$results = &smwfGetStore()->getPropertyValues($subject, $property, $options);

			// prepare navigation bar
			if ($offset > 0)
				$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('PageProperty','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . '&type=' . urlencode($type) .'&from=' . urlencode($from))) . '">' . wfMsg('smw_result_prev') . '</a>';
			else
				$navigation = wfMsg('smw_result_prev');

			$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + min(count($results), $limit)) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

			if (count($results)==($limit+1))
				$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('PageProperty', 'offset=' . ($offset+$limit) . '&limit=' . $limit . '&type=' . urlencode($type) . '&from=' . urlencode($from)))  . '">' . wfMsg('smw_result_next') . '</a>';
			else
				$navigation .= wfMsg('smw_result_next');

			// no need to show the navigation bars when there is not enough to navigate
			if (($offset>0) || (count($results)>$limit)) $html .= '<br />' . $navigation;
			if (count($results) == 0) {
				$html .= wfMsg( 'smw_result_noresults' );
			} else {
				$html .= "<ul>\n";
				$count = $limit+1;
				foreach ($results as $result) {
					$count -= 1;
					if ($count < 1) continue;
					$html .= '<li>' . $result->getLongHTMLText($skin); // do not show infolinks, the magnifier "+" is ambiguous with the browsing '+' for '_wpg' (see below)
					if ($result->getTypeID() == '_wpg') {
						$browselink = SMWInfolink::newBrowsingLink('+',$result->getLongWikiText());
						$html .= ' &nbsp;' . $browselink->getHTML($skin);
					}
					$html .=  "</li> \n";
				}
				$html .= "</ul>\n";
			}
			if (($offset>0) || (count($results)>$limit)) $html .= $navigation;
		}

		// display query form
		$html .= '<p>&nbsp;</p>';
		$html .= '<form name="pageproperty" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
		         '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg('smw_pp_from') . ' <input type="text" name="from" value="' . htmlspecialchars($from) . '" />' . "&nbsp;&nbsp;&nbsp;\n";
		$html .= wfMsg('smw_pp_type') . ' <input type="text" name="type" value="' . htmlspecialchars($type) . '" />' . "\n";
		$html .= '<input type="submit" value="' . wfMsg('smw_pp_submit') . "\"/>\n</form>\n";

		$wgOut->addHTML($html);
		SMWOutputs::commitToOutputPage($wgOut); // make sure locally collected output data is pushed to the output!
	}

}

