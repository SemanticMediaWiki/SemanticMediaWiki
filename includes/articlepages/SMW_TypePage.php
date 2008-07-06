<?php

/**
 * Special handling for relation/attribute description pages.
 * Some code based on CategoryPage.php
 *
 * @author: Markus Krötzsch
 */

/**
 * Implementation of MediaWiki's Article that shows additional information on
 * Type: pages. Very simliar to CategoryPage.
 * @note AUTOLOADED
 */
class SMWTypePage extends SMWOrderedListPage {

	protected $m_typevalue;

	/**
	 * Use higher limit. This operation is very similar to showing members of cateogies.
	 */
	protected function initParameters() {
		global $smwgTypePagingLimit;
		$this->limit = $smwgTypePagingLimit;
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
		$typevalue = SMWDataValueFactory::newTypeIDValue('__typ', $this->mTitle->getText());
		$this->m_typevalue = $typevalue;
		if ($this->from != '') {
			$options->boundary = $this->from;
			$options->ascending = true;
			$options->include_boundary = true;
			$this->articles = $store->getSpecialSubjects(SMW_SP_HAS_TYPE, $typevalue, $options);
		} elseif ($this->until != '') {
			$options->boundary = $this->until;
			$options->ascending = false;
			$options->include_boundary = false;
			$this->articles = array_reverse($store->getSpecialSubjects(SMW_SP_HAS_TYPE, $typevalue, $options));
		} else {
			$this->articles = $store->getSpecialSubjects(SMW_SP_HAS_TYPE, $typevalue, $options);
		}
		foreach ($this->articles as $dv) {
			$this->articles_start_char[] = $wgContLang->convert( $wgContLang->firstChar( $dv->getSortkey() ) );
		}
	}

	/**
	 * Generates the headline for the page list and the HTML encoded list of pages which 
	 * shall be shown.
	 */
	protected function getPages() {
		wfProfileIn( __METHOD__ . ' (SMW)');
		$r = '';
		$typevalue = $this->m_typevalue;
		if ( $typevalue->isBuiltIn() ) {
			$r .= '<p style="font-style: italic; ">' .wfMsg('smw_isknowntype') . "</p>\n";
		}
		/*
		 * TODO: also detect isAlias()? 
		 * But smw_isaliastype message requires determining alias target; 
		 * code is in SMW_SpecialTypes, not SMW_DV_Types.
		 */
		$ti = htmlspecialchars( $this->mTitle->getText() );
		$nav = $this->getNavigationLinks();
		$r .= '<a name="SMWResults"></a>' . $nav . "<div id=\"mw-pages\">\n";

		$r .= '<h2>' . wfMsg('smw_type_header',$ti) . "</h2>\n";
		$r .= wfMsg('smw_typearticlecount', min($this->limit, count($this->articles))) . "\n";

		$r .= $this->formatList();
		$r .= "\n</div>" . $nav;
		wfProfileOut( __METHOD__ . ' (SMW)');
		return $r;
	}

	/**
	 * Format a list of articles chunked by letter, either as a
	 * bullet list or a columnar format, depending on the length.
	 *
	 * @param int   $cutoff
	 * @return string
	 */
	private function formatList( $cutoff = 6 ) {
		$end = count($this->articles);
		if ($end > $this->limit) {
			if ($this->until != '') {
				$start = 1;
			} else {
				$start = 0;
				$end --;
			}
		} else {
			$start = 0;
		}

		if ( count ( $this->articles ) > $cutoff ) {
			return $this->columnList( $start, $end );
		} elseif ( count($this->articles) > 0) {
			// for short lists of articles
			return $this->shortList( $start, $end );
		}
		return '';
	}

	/**
	 * Format a list of articles chunked by letter in a three-column
	 * list, ordered vertically.
	 */
	private function columnList($start, $end) {
		// divide list into three equal chunks
		$chunk = (int) ( ($end-$start+1) / 3);

		// get and display header
		$r = '<table width="100%"><tr valign="top">';

		$prev_start_char = 'none';

		// loop through the chunks
		for($startChunk = $start, $endChunk = $chunk, $chunkIndex = 0;
			$chunkIndex < 3;
			$chunkIndex++, $startChunk = $endChunk, $endChunk += $chunk + 1) {
			$r .= "<td>\n";
			$atColumnTop = true;

			// output all articles
			for ($index = $startChunk ;
				$index < $endChunk && $index < $end;
				$index++ ) {
				// check for change of starting letter or begining of chunk
				if ( ($index == $startChunk) ||
					 ($this->articles_start_char[$index] != $this->articles_start_char[$index - 1]) ) {
					if( $atColumnTop ) {
						$atColumnTop = false;
					} else {
						$r .= "</ul>\n";
					}
					$cont_msg = "";
					if ( $this->articles_start_char[$index] == $prev_start_char )
						$cont_msg = wfMsgHtml('listingcontinuesabbrev');
					$r .= "<h3>" . htmlspecialchars( $this->articles_start_char[$index] ) . " $cont_msg</h3>\n<ul>";
					$prev_start_char = $this->articles_start_char[$index];
				}
				$r .= "<li>" . $this->articles[$index]->getLongHTMLText($this->getSkin()) . "</li>\n";
			}
			if( !$atColumnTop ) {
				$r .= "</ul>\n";
			}
			$r .= "</td>\n";
		}
		$r .= '</tr></table>';
		return $r;
	}

	/**
	 * Format a list of articles chunked by letter in a bullet list.
	 */
	private function shortList($start, $end) {
		$r = '<h3>' . htmlspecialchars( $this->articles_start_char[$start] ) . "</h3>\n";
		$r .= '<ul><li>'. $this->articles[$start]->getLongHTMLText($this->getSkin()) . '</li>';
		for ($index = $start+1; $index < $end; $index++ ) {
			if ($this->articles_start_char[$index] != $this->articles_start_char[$index - 1]) {
				$r .= "</ul><h3>" . htmlspecialchars( $this->articles_start_char[$index] ) . "</h3>\n<ul>";
			}
			$r .= '<li>' . $this->articles[$index]->getLongHTMLText($this->getSkin()) . '</li>';
		}
		$r .= '</ul>';
		return $r;
	}

}

