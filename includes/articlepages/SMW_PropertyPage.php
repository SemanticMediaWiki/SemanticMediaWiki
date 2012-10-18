<?php

/**
 * Implementation of MediaWiki's Article that shows additional information on
 * property pages. Very similar to CategoryPage, but with different printout
 * that also displays values for each subject with the given property.
 *
 * @file SMW_PropertyPage.php
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */
class SMWPropertyPage extends SMWOrderedListPage {

	/**
	 * @see SMWOrderedListPage::initParameters()
	 * @note We use a smaller limit here; property pages might become large.
	 */
	protected function initParameters() {
		global $smwgPropertyPagingLimit;
		$this->limit = $smwgPropertyPagingLimit;
		$this->mProperty = SMWDIProperty::newFromUserLabel( $this->mTitle->getText() );
		return true;
	}

	/**
	 * Returns the HTML which is added to $wgOut after the article text.
	 *
	 * @return string
	 */
	protected function getHtml() {
		wfProfileIn( __METHOD__ . ' (SMW)' );

		$result = $this->getSubpropertyList() . $this->getPropertyValueList();

		wfProfileOut( __METHOD__ . ' (SMW)' );
		return $result;
	}

	/**
	 * Get the HTML for displaying subproperties of this property. This list
	 * is usually short and we implement no additional navigation.
	 *
	 * @return string
	 */
	protected function getSubpropertyList() {
		$store = smwfGetStore();
		$options = new SMWRequestOptions();
		$options->sort = true;
		$options->ascending = true;
		$subproperties = $store->getPropertySubjects( new SMWDIProperty( '_SUBP' ), $this->getDataItem(), $options );

		$result = '';

		$resultCount = count( $subproperties );
		if ( $resultCount > 0 ) {
			$titleText = htmlspecialchars( $this->mTitle->getText() );
			$result .= "<div id=\"mw-subcategories\">\n<h2>" . wfMessage( 'smw_subproperty_header', $titleText )->text() . "</h2>\n<p>";

			if ( !$this->mProperty->isUserDefined() ) {
				$result .= wfMessage( 'smw_isspecprop' )->text() . ' ';
			}

			 $result .= wfMsgExt( 'smw_subpropertyarticlecount', array( 'parsemag' ), $resultCount ) . "</p>\n";

			if ( $resultCount < 6 ) {
				$result .= SMWPageLister::getShortList( 0, $resultCount, $subproperties, null );
			} else {
				$result .= SMWPageLister::getColumnList( 0, $resultCount, $subproperties, null );
			}

			$result .= "\n</div>";
		}

		return $result;
	}

	/**
	 * Get the HTML for displaying values of this property, based on the
	 * current from/until and limit settings.
	 *
	 * @return string
	 */
	protected function getPropertyValueList() {
		if ( $this->limit > 0 ) { // limit==0: configuration setting to disable this completely
			$store = smwfGetStore();
			$options = SMWPageLister::getRequestOptions( $this->limit, $this->from, $this->until );
			$diWikiPages = $store->getAllPropertySubjects( $this->mProperty, $options );

			if ( !$options->ascending ) {
				$diWikiPages = array_reverse( $diWikiPages );
			}
		} else {
			return '';
		}

		$result = '';

		if ( count( $diWikiPages ) > 0 ) {
			$pageLister = new SMWPageLister( $diWikiPages, null, $this->limit, $this->from, $this->until );
			$this->mTitle->setFragment( '#SMWResults' ); // Make navigation point to the result list.
			$navigation = $pageLister->getNavigationLinks( $this->mTitle );

			$titleText = htmlspecialchars( $this->mTitle->getText() );
			$resultNumber = min( $this->limit, count( $diWikiPages ) );

			$result .= "<a name=\"SMWResults\"></a><div id=\"mw-pages\">\n" .
			           '<h2>' . wfMessage( 'smw_attribute_header', $titleText )->text() . "</h2>\n<p>";

			if ( !$this->mProperty->isUserDefined() ) {
				$result .= wfMessage( 'smw_isspecprop' )->text() . ' ';
			}

			$result .= wfMsgExt( 'smw_attributearticlecount', array( 'parsemag' ), $resultNumber ) . "</p>\n" .
			           $navigation . $this->subjectObjectList( $diWikiPages ) . $navigation . "\n</div>";
		}

		return $result;
	}

	/**
	 * Format $diWikiPages chunked by letter in a table that shows subject
	 * articles in one column and object articles/values in the other one.
	 *
	 * @param $diWikiPages array
	 * @return string
	 */
	protected function subjectObjectList( array $diWikiPages ) {
		global $wgContLang, $smwgMaxPropertyValues;
		$store = smwfGetStore();

		$ac = count( $diWikiPages );

		if ( $ac > $this->limit ) {
			if ( $this->until !== '' ) {
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
			$diWikiPage = $diWikiPages[$index];
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
			$r .= '<tr><td class="smwpropname">' . $dvWikiPage->getShortHTMLText( smwfGetLinker() ) .
			      '&#160;' . $searchlink->getHTML( smwfGetLinker() ) . '</td><td class="smwprops">';

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
					$r .= $dv->getShortHTMLText( smwfGetLinker() ) . $dv->getInfolinkText( SMW_OUTPUT_HTML, smwfGetLinker() );
				} else {
					$searchlink = SMWInfolink::newInversePropertySearchLink( '…', $dvWikiPage->getWikiValue(), $this->mTitle->getText() );
					$r .= $searchlink->getHTML( smwfGetLinker() );
				}
			}

			$r .= "</td></tr>\n";
		}

		$r .= '</table>';

		return $r;
	}

}
