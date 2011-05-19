<?php

/**
 * Implementation of MediaWiki's Article that shows additional information on
 * property pages. Very similar to CategoryPage, but with different printout
 * that also displays values for each subject with the given property.
 * 
 * Some code based on CategoryPage.php
 *
 * @file SMW_PropertyPage.php
 * @ingroup SMW
 * 
 * @author Markus Krötzsch
 */
class SMWPropertyPage extends SMWOrderedListPage {

	private $subproperties;  // list of sub-properties of this property

	/**
	 * @see SMWOrderedListPage::initParameters()
	 * @note We use a smaller limit here; property pages might become large.
	 */
	protected function initParameters() {
		global $smwgPropertyPagingLimit;
		$this->limit = $smwgPropertyPagingLimit;
		$this->mProperty = new SMWDIProperty( $this->mTitle->getDBkey(), false );
		return true;
	}

	protected function clearPageState() {
		parent::clearPageState();
		$this->subproperties = array();
	}

	/**
	 * Fill the internal arrays with the set of data items to be displayed
	 * (possibly plus one additional item that indicates further results).
	 */
	protected function doQuery() {
		$store = smwfGetStore();
		
		if ( $this->limit > 0 ) { // for limit==0 there is no paging, and no query
			$options = new SMWRequestOptions();
			$options->limit = $this->limit + 1;
			$options->sort = true;
			$reverse = false;
			
			if ( $this->from != '' ) {
				$options->boundary = $this->from;
				$options->ascending = true;
				$options->include_boundary = true;
			} elseif ( $this->until != '' ) {
				$options->boundary = $this->until;
				$options->ascending = false;
				$options->include_boundary = false;
				$reverse = true;
			}
			
			$this->diWikiPages = $store->getAllPropertySubjects( $this->mProperty, $options );
			
			if ( $reverse ) {
				$this->diWikiPages = array_reverse( $this->diWikiPages );
			}
		} else {
			$this->diWikiPages = array();
		}

		// retrieve all subproperties of this property
		$s_options = new SMWRequestOptions();
		$s_options->sort = true;
		$s_options->ascending = true;
		$this->subproperties = $store->getPropertySubjects( new SMWDIProperty( '_SUBP' ), $this->getDataItem(), $s_options );
	}

	/**
	 * Generates the headline for the page list and the HTML encoded list
	 * of pages which shall be shown.
	 */
	protected function getPages() {
		wfProfileIn( __METHOD__ . ' (SMW)' );
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$r = '';
		$ti = htmlspecialchars( $this->mTitle->getText() );
		
		if ( count( $this->subproperties ) > 0 ) {
			$r .= "<div id=\"mw-subcategories\">\n<h2>" . wfMsg( 'smw_subproperty_header', $ti ) . "</h2>\n<p>";
			
			if ( !$this->mProperty->isUserDefined() ) {
				$r .= wfMsg( 'smw_isspecprop' ) . ' ';
			}
			
			$r .= wfMsgExt( 'smw_subpropertyarticlecount', array( 'parsemag' ), count( $this->subproperties ) ) . "</p>\n";
			$r .= ( count( $this->subproperties ) < 6 ) ?
			      $this->shortList( 0, count( $this->subproperties ), $this->subproperties ):
				  $this->columnList( 0, count( $this->subproperties ), $this->subproperties );
				  
			$r .= "\n</div>";
		}
		
		if ( count( $this->diWikiPages ) > 0 ) {
			$nav = $this->getNavigationLinks();
			
			$r .= '<a name="SMWResults"></a>' . $nav . "<div id=\"mw-pages\">\n" .
			      '<h2>' . wfMsg( 'smw_attribute_header', $ti ) . "</h2>\n<p>";
			
			if ( !$this->mProperty->isUserDefined() ) {
				$r .= wfMsg( 'smw_isspecprop' ) . ' ';
			}
			
			$r .= wfMsgExt( 'smw_attributearticlecount', array( 'parsemag' ), min( $this->limit, count( $this->diWikiPages ) ) ) . "</p>\n" .
			      $this->subjectObjectList() . "\n</div>" . $nav;
		}
		
		wfProfileOut( __METHOD__ . ' (SMW)' );
		
		return $r;
	}

	/**
	 * Format $diWikiPages chunked by letter in a table that shows subject
	 * articles in one column and object articles/values in the other one.
	 */
	private function subjectObjectList() {
		global $wgContLang, $smwgMaxPropertyValues;
		$store = smwfGetStore();

		$ac = count( $this->diWikiPages );
		
		if ( $ac > $this->limit ) {
			if ( $this->until != '' ) {
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
		
		for ( $index = $start; $index < $ac; $index++ ) {
			$diWikiPage = $this->diWikiPages[$index];
			$dvWikiPage = SMWDataValueFactory::newDataItemValue( $diWikiPage, null );
			$sortkey = smwfGetStore()->getWikiPageSortKey( $diWikiPage );
			$start_char = $wgContLang->convert( $wgContLang->firstChar( $sortkey ) );
			
			// Header for index letters
			if ( $start_char != $prev_start_char ) {
				$r .= '<tr><th class="smwpropname"><h3>' . htmlspecialchars( $start_char ) . "</h3></th><th></th></tr>\n";
				$prev_start_char = $start_char;
			}
			
			// Property name
			$searchlink = SMWInfolink::newBrowsingLink( '+', $dvWikiPage->getShortHTMLText() );
			$r .= '<tr><td class="smwpropname">' . $dvWikiPage->getLongHTMLText( $this->getSkin() ) .
			      '&#160;' . $searchlink->getHTML( $this->getSkin() ) . '</td><td class="smwprops">';
			
			// Property values
			$ropts = new SMWRequestOptions();
			$ropts->limit = $smwgMaxPropertyValues + 1;
			$values = $store->getPropertyValues( $diWikiPage, $this->mProperty, $ropts );
			$i = 0;
			
			foreach ( $values as $di ) {
				if ( $i != 0 ) {
					$r .= ', ';
				}
				$i++;

				if ( $i < $smwgMaxPropertyValues + 1 ) {
					$dv = SMWDataValueFactory::newDataItemValue( $di, $this->mProperty );
					$r .= $dv->getLongHTMLText( $this->getSkin() ) . $dv->getInfolinkText( SMW_OUTPUT_HTML, $this->getSkin() );
				} else {
					$searchlink = SMWInfolink::newInversePropertySearchLink( '…', $dvWikiPage->getWikiValue(), $this->mTitle->getText() );
					$r .= $searchlink->getHTML( $this->getSkin() );
				}
			}

			$r .= "</td></tr>\n";
		}
		
		$r .= '</table>';
		
		return $r;
	}

}