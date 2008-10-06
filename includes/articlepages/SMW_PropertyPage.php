<?php
/**
 * Special handling for property description pages.
 * Some code based on CategoryPage.php
 *
 * @author: Markus Krötzsch
 * @file
 * @ingroup SMW
 */

/**
 * Implementation of MediaWiki's Article that shows additional information on
 * property pages. Very simliar to CategoryPage, but with different printout 
 * that also displays values for each subject with the given property.
 * @ingroup SMW
 */
class SMWPropertyPage extends SMWOrderedListPage {

	protected $special_prop; // code number of special property, false if not.
	private $subproperties;  // list of sub-properties of this property

	/**
	 * Use small $limit (property pages might become large)
	 */
	protected function initParameters() {
		global $smwgContLang, $smwgPropertyPagingLimit;
		$this->limit = $smwgPropertyPagingLimit;
		$this->special_prop = $smwgContLang->findSpecialPropertyID($this->mTitle->getText(), false);
		return true;
	}

	protected function clearPageState() {
		parent::clearPageState();
		$this->subproperties = array();
	}

	/**
	 * Fill the internal arrays with the set of articles to be displayed (possibly plus one additional
	 * article that indicates further results).
	 */
	protected function doQuery() {
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

		// retrieve all subproperties of this property
		$s_options = new SMWRequestOptions();
		$s_options->sort = true;
		$s_options->ascending = true;
		$this->subproperties = $store->getSpecialSubjects(SMW_SP_SUBPROPERTY_OF, $this->getDataValue(), $s_options);
	}

	/**
	 * Generates the headline for the page list and the HTML encoded list of pages which
	 * shall be shown.
	 */
	protected function getPages() {
		wfProfileIn( __METHOD__ . ' (SMW)');
		wfLoadExtensionMessages('SemanticMediaWiki');
		$r = '';
		$ti = htmlspecialchars( $this->mTitle->getText() );
		if ($this->special_prop !== false) {
			$r .= '<p>' .wfMsg('smw_isspecprop') . "</p>\n";
		} else {
			$nav = $this->getNavigationLinks();
			if (count($this->subproperties) > 0) {
				$r .= "<div id=\"mw-subcategories\">\n<h2>" . wfMsg('smw_subproperty_header',$ti) . "</h2>\n";
				$r .= '<p>' . wfMsgExt('smw_subpropertyarticlecount', array( 'parsemag' ), min($this->limit, count($this->subproperties))) . "</p>\n";
				if (count($this->subproperties) < 6) {
					$r .= $this->shortList(0,count($this->subproperties), $this->subproperties);
				} else {
					$r .= $this->columnList(0,count($this->subproperties), $this->subproperties);
				}
				$r .= "\n</div>";
			}
			$r .= '<a name="SMWResults"></a>' . $nav . "<div id=\"mw-pages\">\n";
			$r .= '<h2>' . wfMsg('smw_attribute_header',$ti) . "</h2>\n";
			$r .= '<p>' . wfMsgExt('smw_attributearticlecount', array( 'parsemag' ), min($this->limit, count($this->articles))) . "</p>\n";
			$r .= $this->subjectObjectList() . "\n</div>" . $nav;
		}
		wfProfileOut( __METHOD__ . ' (SMW)');
		return $r;
	}

	/**
	 * Format a list of articles chunked by letter in a table that shows subject articles in
	 * one column and object articles/values in the other one.
	 */
	private function subjectObjectList() {
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
		$prev_start_char = 'None';
		for ($index = $start; $index < $ac; $index++ ) {
			$start_char = $wgContLang->convert( $wgContLang->firstChar( $this->articles[$index]->getSortkey() ) );
			// Header for index letters
			if ($start_char != $prev_start_char) {
				$r .= '<tr><th class="smwpropname"><h3>' . htmlspecialchars( $start_char ) . "</h3></th><th></th></tr>\n";
				$prev_start_char = $start_char;
			}
			// Property name
			$searchlink = SMWInfolink::newBrowsingLink('+',$this->articles[$index]->getShortHTMLText());
			$r .= '<tr><td class="smwpropname">' . $this->articles[$index]->getLongHTMLText($this->getSkin()) .
			      '&nbsp;' . $searchlink->getHTML($this->getSkin()) . '</td><td class="smwprops">';
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
					$searchlink = SMWInfolink::newInversePropertySearchLink('&hellip;', $this->articles[$index]->getWikiValue(), $this->mTitle->getText());
					$r .= $searchlink->getHTML($this->getSkin());
				}
			}
			$r .= "</td></tr>\n";
		}
		$r .= '</table>';
		return $r;
	}
}


