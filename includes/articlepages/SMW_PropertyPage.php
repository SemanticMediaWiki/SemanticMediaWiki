<?php
/**
 * Special handling for property description pages.
 * Some code based on CategoryPage.php
 *
 * @author: Markus Krötzsch
 */

if( !defined( 'MEDIAWIKI' ) )   die( 1 );

global $smwgIP;
require_once( "$smwgIP/includes/articlepages/SMW_OrderedListPage.php");

/**
 * Implementation of MediaWiki's Article that shows additional information on
 * property pages. Very simliar to CategoryPage, but with different printout 
 * that also displays values for each subject with the given property.
 */
class SMWPropertyPage extends SMWOrderedListPage {

	protected $special_prop; // code number of special property, false if not.

	/**
	 * Use small $limit (property pages might become large)
	 */
	protected function initParameters() {
		global $smwgContLang, $smwgPropertyPagingLimit;
		$this->limit = $smwgPropertyPagingLimit;
		$this->special_prop = $smwgContLang->findSpecialPropertyID($this->mTitle->getText(), false);
		return true;
	}

	/**
	 * Fill the internal arrays with the set of articles to be displayed (possibly plus one additional
	 * article that indicates further results).
	 */
	protected function doQuery() {
		global $wgContLang;

		$store = smwfGetStore();
		$options = new SMWRequestOptions();
		$options->limit = $this->limit + 1;
		$options->sort = true;
		$reverse = false;
		if ($this->from != '') {
			$options->boundary = $this->from;
			$options->ascending = true;
			$options->include_boundary = true;
		} elseif ($this->until != '') {
			$options->boundary = $this->until;
			$options->ascending = false;
			$options->include_boundary = false;
			$reverse = true;
		}
		if ($this->special_prop === false) {
			$this->articles = $store->getAllPropertySubjects($this->mTitle, $options);
		} else {
			// For now, do not attempt listings for special properties:
			// they behave differently, have dedicated search UIs, and
			// might even be unsearchable by design
			return;
		}
		if ($reverse) {
			$this->articles = array_reverse($this->articles);
		}

		foreach ($this->articles as $title) {
			$this->articles_start_char[] = $wgContLang->convert( $wgContLang->firstChar( $title->getText() ) );
		}
	}

	/**
	 * Generates the headline for the page list and the HTML encoded list of pages which
	 * shall be shown.
	 */
	protected function getPages() {
		wfProfileIn( __METHOD__ . ' (SMW)');
		$r = '';
		$ti = htmlspecialchars( $this->mTitle->getText() );
		if ($this->special_prop !== false) {
			$r .= '<p>' .wfMsg('smw_isspecprop') . "</p>\n";
		} else {		
			$nav = $this->getNavigationLinks();
			$r .= '<a name="SMWResults"></a>' . $nav . "<div id=\"mw-pages\">\n";
			$r .= '<h2>' . wfMsg('smw_attribute_header',$ti) . "</h2>\n";
			$r .= wfMsg('smw_attributearticlecount', min($this->limit, count($this->articles))) . "\n";
			$r .= $this->shortList( $this->articles, $this->articles_start_char ) . "\n</div>" . $nav;
		}
		wfProfileOut( __METHOD__ . ' (SMW)');
		return $r;
	}

	/**
	 * Format a list of articles chunked by letter in a table that shows subject articles in
	 * one column and object articles/values in the other one.
	 */
	private function shortList() {
		global $wgContLang;
		$store = smwfGetStore();

		$ac = count($this->articles);
		if ($ac > $this->limit) {
			if ($this->until != '') {
				$start = 1;
			} else {
				$start = 0;
				$ac = $ac - 1;
			}
		} else {
			$start = 0;
		}

		$r = '<table style="width: 100%; ">';
		$prevchar = 'None';
		for ($index = $start; $index < $ac; $index++ ) {
			global $smwgIP;
			include_once($smwgIP . '/includes/SMW_Infolink.php');
			// Header for index letters
			if ($this->articles_start_char[$index] != $prevchar) {
				$r .= '<tr><th class="smwpropname"><h3>' . htmlspecialchars( $this->articles_start_char[$index] ) . "</h3></th><th></th></tr>\n";
				$prevchar = $this->articles_start_char[$index];
			}
			// Property name
			$searchlink = SMWInfolink::newBrowsingLink('+',$this->articles[$index]->getPrefixedText());
			$r .= '<tr><td class="smwpropname">' . $this->getSkin()->makeKnownLinkObj( $this->articles[$index], 
			  $wgContLang->convert( $this->articles[$index]->getPrefixedText() ) ) . 
			  '&nbsp;' . $searchlink->getHTML($this->getSkin()) .
			  '</td><td class="smwprops">';
			// Property values
			$ropts = new SMWRequestOptions();
			$ropts->limit = 4;
			$values = $store->getPropertyValues($this->articles[$index], $this->mTitle, $ropts);
			$i=0;
			foreach ($values as $value) {
				if ($i != 0) {
					$r .= ', ';
				}
				$i++;
				if ($i < 4) {
					$r .= $value->getLongHTMLText($this->getSkin()) . $value->getInfolinkText(SMW_OUTPUT_HTML, $this->getSkin());
				} else {
					$searchlink = SMWInfolink::newInversePropertySearchLink('&hellip;', $this->articles[$index]->getPrefixedText(), $this->mTitle->getText());
					$r .= $searchlink->getHTML($this->getSkin());
				}
			}
			$r .= "</td></tr>\n";
		}
		$r .= '</table>';
		return $r;
	}
}


