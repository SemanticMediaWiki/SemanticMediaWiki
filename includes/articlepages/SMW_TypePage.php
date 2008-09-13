<?php
/**
 * Special handling for type description pages.
 * Some code based on CategoryPage.php
 *
 * @author: Markus Krötzsch
 * @file
 * @ingroup SMW
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
	}

	/**
	 * Generates the headline for the page list and the HTML encoded list of pages which 
	 * shall be shown.
	 */
	protected function getPages() {
		wfProfileIn( __METHOD__ . ' (SMW)');
		wfLoadExtensionMessages('SemanticMediaWiki');
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
		$r .= wfMsgExt('smw_typearticlecount', array( 'parsemag' ), min($this->limit, count($this->articles))) . "\n";

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
			return $this->columnList( $start, $end, $this->articles );
		} elseif ( count($this->articles) > 0) {
			// for short lists of articles
			return $this->shortList( $start, $end, $this->articles );
		}
		return '';
	}

}

