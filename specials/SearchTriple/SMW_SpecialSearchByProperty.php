<?php

/**
 * @author Denny Vrandecic
 *
 * This special page for Semantic MediaWiki implements a
 * view on a relation-object pair, i.e. a typed baclink.
 * For example, it shows me all persons born in Croatia,
 * or all winners of the Academy Award for best actress.
 *
 * @note AUTOLOADED
 */
class SMWSearchByProperty extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('SearchByProperty');
		//the key defining the group name in the language files is specialpages-group-smw_group
		if (method_exists('SpecialPage', 'setGroup')) { 
			parent::setGroup('SearchByProperty', 'smw_group');	
		}
	}

	public function execute($query = '') {
		global $wgRequest, $wgOut, $wgUser, $smwgQMaxInlineLimit;
		$skin = $wgUser->getSkin();

		// get the GET parameters
		$attributestring = $wgRequest->getVal( 'property' );
		$valuestring = $wgRequest->getVal( 'value' );
		$params = SMWInfolink::decodeParameters($query, false);
		reset($params);
		// no GET parameters? Then try the URL
		if ($attributestring == '') $attributestring = current($params);
		if ($valuestring == '') $valuestring = next($params);

		$attribute = Title::newFromText( $attributestring, SMW_NS_PROPERTY );
		if (NULL === $attribute) { $attributestring = ''; } else { $attributestring = $attribute->getText(); }

		$limit = $wgRequest->getVal( 'limit' );
		if ('' == $limit) $limit =  20;
		$offset = $wgRequest->getVal( 'offset' );
		if ('' == $offset) $offset = 0;
		$html = '';
		$spectitle = Title::makeTitle( NS_SPECIAL, 'SearchByProperty' );

		if ('' == $attributestring) { // empty page. If no attribute given the value does not matter
			$html .= wfMsg('smw_sbv_docu') . "\n";
		} else {
			// Now that we have an attribute, let's figure out the datavalue
			$value = SMWDataValueFactory::newPropertyObjectValue( $attribute, $valuestring );
			if ( $value->isValid() == FALSE ) { // no value understood
				$html .= wfMSG('smw_sbv_novalue', $skin->makeLinkObj($attribute, $attribute->getText()));
				$valuestring = '';
			} else { // everything is given
				$wgOut->setPagetitle( $attribute->getText() . ' ' . $value->getShortHTMLText(NULL) );
				$valuestring = $value->getWikiValue();

				$options = new SMWRequestOptions();
				$options->limit = $limit+1;
				$options->offset = $offset;

				$res = &smwfGetStore()->getPropertySubjects( $attribute, $value, $options );
				$count = count($res);


				$html .= wfMsg('smw_sbv_displayresult', $skin->makeLinkObj($attribute, $attribute->getText()), $value->getShortHTMLText($skin)) . "<br />\n";

				// prepare navigation bar
				if ($offset > 0)
					$navigation = '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SearchByProperty','offset=' . max(0,$offset-$limit) . '&limit=' . $limit . '&property=' . urlencode($attribute->getText()) .'&value=' . urlencode($value->getWikiValue()))) . '">' . wfMsg('smw_result_prev') . '</a>';
				else
					$navigation = wfMsg('smw_result_prev');

				$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp; <b>' . wfMsg('smw_result_results') . ' ' . ($offset+1) . '&ndash; ' . ($offset + min($count, $limit)) . '</b>&nbsp;&nbsp;&nbsp;&nbsp;';

				if ($count>$limit) {
					$navigation .= ' <a href="' . htmlspecialchars($skin->makeSpecialUrl('SearchByProperty', 'offset=' . ($offset+$limit) . '&limit=' . $limit . '&property=' . urlencode($attribute->getText()) . '&value=' . urlencode($value->getWikiValue())))  . '">' . wfMsg('smw_result_next') . '</a>';
				} else {
					$navigation .= wfMsg('smw_result_next');
				}

				$max = false; $first=true;
				foreach (array(20,50,100,250,500) as $l) {
					if ($max) continue;
					if ($first) {
						$navigation .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(';
						$first = false;
					} else
						$navigation .= ' | ';
					if ($l > $smwgQMaxInlineLimit) {
						$l = $smwgQMaxInlineLimit;
						$max = true;
					}
					if ( $limit != $l ) {
						$navigation .= '<a href="' . htmlspecialchars($skin->makeSpecialUrl('SearchByProperty','offset=' . $offset . '&limit=' . $l . '&property=' . urlencode($attribute->getText()) . '&value=' . urlencode($value->getWikiValue()))) . '">' . $l . '</a>';
					} else {
						$navigation .= '<b>' . $l . '</b>';
					}
				}
				$navigation .= ')';

// TODO The following code can be turned into a list of similar result, instead of
// exact results one day. It is unclear, if this should go over the iq or if rather
// the storage API should be extended. The iq part is outlined here, but does not
// really work, and would require another query printer as it is outlined here.

//				if ($count < ($limit/2)) { // if there is only a small number of results
//					$iqoptions = array();
//					$iqoptions['limit'] = $limit/2+$count;
//					$iqoptions['sort'] = $attribute->getText();
//					$iqoptions['order'] = 'DESC';
//					$iqoptions['format'] = 'ul';
//					$iqoptions['headers'] = 'hide';
//					$iq = new SMWInlineQuery($iqoptions, FALSE);
//					$html .= $iq->getHTMLResult("[[" . $attribute->getText() . ":=<" . $value->getUserValue() . "]] [[" . $attribute->getText() . ":=*]]");
//					$iqoptions['offset'] = $count;
//					$iqoptions['order'] = 'ASC';
//					$iqoptions['limit'] = $limit/2-$count;
//					$iq = new SMWInlineQuery($iqoptions, FALSE);
//					$html .= $iq->getHTMLResult("[[" . $attribute->getText() . ":=>" . $value->getUserValue() . "]] [[" . $attribute->getText() . ":=*]]");
//				}

				if ($count == 0) {
					$html .= wfMsg( 'smw_result_noresults' );
				} else { // if there are plenty of results anyway
					global $smwgIP;
					// no need to show the navigation bars when there is not enough to navigate
					if (($offset>0) || ($count>$limit)) $html .= '<br />' . $navigation;
					$html .= "<ul>\n";
					foreach ($res as $dv) {
						$browselink = SMWInfolink::newBrowsingLink('+',$dv->getShortHTMLText());
						$html .= '<li>' . $dv->getShortHTMLText($skin) . '&nbsp;&nbsp;' . $browselink->getHTML($skin) . "</li> \n";
					}
					$html .= "</ul>\n";
					if (($offset>0) || ($count>$limit)) $html .= $navigation;
				}
			}
		}

		// display query form
		$html .= '<p>&nbsp;</p>';
		$html .= '<form name="searchbyproperty" action="' . $spectitle->escapeLocalURL() . '" method="get">' . "\n" .
		         '<input type="hidden" name="title" value="' . $spectitle->getPrefixedText() . '"/>' ;
		$html .= wfMsg('smw_sbv_property') . ' <input type="text" name="property" value="' . htmlspecialchars($attributestring) . '" />' . "&nbsp;&nbsp;&nbsp;\n";
		$html .= wfMsg('smw_sbv_value') . ' <input type="text" name="value" value="' . htmlspecialchars($valuestring) . '" />' . "\n";
		$html .= '<input type="submit" value="' . wfMsg('smw_sbv_submit') . "\"/>\n</form>\n";

		$wgOut->addHTML($html);
	}

}

