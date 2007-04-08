<?php
/**
 * Special handling for relation/attribute description pages.
 * Some code based on CategoryPage.php
 *
 * @package MediaWiki
 * @author: Markus Krötzsch
 */

if( !defined( 'MEDIAWIKI' ) ) {
	die( 1 );
}

global $smwgIP;
require_once( "$smwgIP/includes/articlepages/SMW_OrderedListPage.php");

/**
 */
class SMWPropertyPage extends SMWOrderedListPage {

	public function SMWPropertyPage() {
		$this->limit = 25;
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
		if ( $this->mTitle->getNamespace()== SMW_NS_RELATION ) {
			$this->articles = $store->getAllRelationSubjects($this->mTitle, $options);
		} else {
			$this->articles = $store->getAllAttributeSubjects($this->mTitle, $options);
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
		$ti = htmlspecialchars( $this->mTitle->getText() );
		$nav = $this->getNavigationLinks();
		$r = $nav . "<div id=\"mw-pages\">\n";
		switch ( $this->mTitle->getNamespace() ) {
			case SMW_NS_RELATION:
				$r .= '<h2>' . wfMsg('smw_relation_header',$ti) . "</h2>\n";
				$r .= wfMsg('smw_relationarticlecount', min($this->limit, count($this->articles))) . "\n";
				break;
			case SMW_NS_ATTRIBUTE:
				$r .= '<h2>' . wfMsg('smw_relation_header',$ti) . "</h2>\n";
				$r .= wfMsg('smw_relationarticlecount', min($this->limit, count($this->articles))) . "\n";
				break;
		}
		$r .= $this->shortList( $this->articles, $this->articles_start_char ) . "\n</div>" . $nav;
		return $r;
	}

	/**
	 * Format a list of articles chunked by letter in a bullet list.
	 * @param array $articles
	 * @param array $articles_start_char
	 * @return string
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
			// Header for index letters
			if ($this->articles_start_char[$index] != $prevchar) {
				$r .= '<tr><th class="smwattname"><h3>' . htmlspecialchars( $this->articles_start_char[$index] ) . "</h3></th><th></th></tr>\n";
				$prevchar = $this->articles_start_char[$index];
			}
			// Attribute/relation name
			$r .= '<tr><td class="smwattname">' . $this->getSkin()->makeKnownLinkObj( $this->articles[$index], 
			  $wgContLang->convert( $this->articles[$index]->getPrefixedText() ) ) .
			  '</td><td class="smwatts">';
			// Attribute/relation values
			if ($this->mTitle->getNamespace() == SMW_NS_RELATION) {
				$objects = $store->getRelationObjects($this->articles[$index], $this->mTitle);
				$l = count($objects);
				$i=0;
				foreach ($objects as $object) {
					if ($i != 0) {
						if ($i > $l-2) {
							$r .= wfMsgForContent('smw_finallistconjunct') . ' ';
						} else {
							$r .= ', ';
						}
					}
					$i++;
					$searchlink = SMWInfolink::newRelationSearchLink('+',$this->mTitle->getText(),$object->getPrefixedText());
					$r .= $this->getSkin()->makeLinkObj($object, $wgContLang->convert( $object->getText() )) . '&nbsp;&nbsp;' . $searchlink->getHTML($this->getSkin());
				}
			} elseif ($this->mTitle->getNamespace() == SMW_NS_ATTRIBUTE) {
				$values = $store->getAttributeValues($this->articles[$index], $this->mTitle);
				$l = count($values);
				$i=0;
				foreach ($values as $value) {
					if ($i != 0) {
						if ($i > $l-2) {
							$r .= wfMsgForContent('smw_finallistconjunct') . ' ';
						} else {
							$r .= ', ';
						}
					}
					$i++;
					$r .= $value->getValueDescription(); 
					$sep = '&nbsp;&nbsp;';
					foreach ($value->getInfolinks() as $link) {
						$r .= $sep . $link->getHTML($this->getSkin());
						$sep = ' &nbsp;&nbsp;'; // allow breaking for longer lists of infolinks
					}
				}
			}
			$r .= "</td></tr>\n";
		}
		$r .= '</table>';
		return $r;
	}

}

?>
