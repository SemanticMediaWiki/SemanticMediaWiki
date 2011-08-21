<?php
/**
 * The class in this file provides means of rendering a "Factbox" in articles.
 * @file
 * @ingroup SMW
 * @author Markus Krötzsch
 */

/**
 * Static class for printing semantic data in a "Factbox".
 * @ingroup SMW
 */
class SMWFactbox {

	/**
	 * This function creates wiki text suitable for rendering a Factbox for a given
	 * SMWSemanticData object that holds all relevant data. It also checks whether the
	 * given setting of $showfactbox requires displaying the given data at all.
	 */
	static public function getFactboxText( SMWSemanticData $semdata, $showfactbox = SMW_FACTBOX_NONEMPTY ) {
		global $wgContLang;
		wfProfileIn( 'SMWFactbox::printFactbox (SMW)' );
		switch ( $showfactbox ) {
			case SMW_FACTBOX_HIDDEN: // never show
				wfProfileOut( 'SMWFactbox::printFactbox (SMW)' );
			return '';
			case SMW_FACTBOX_SPECIAL: // show only if there are special properties
				if ( !$semdata->hasVisibleSpecialProperties() ) {
					wfProfileOut( 'SMWFactbox::printFactbox (SMW)' );
					return '';
				}
			break;
			case SMW_FACTBOX_NONEMPTY: // show only if non-empty
				if ( !$semdata->hasVisibleProperties() ) {
					wfProfileOut( 'SMWFactbox::printFactbox (SMW)' );
					return '';
				}
			break;
			// case SMW_FACTBOX_SHOWN: // just show ...
		}

		// actually build the Factbox text:
		$text = '';
		if ( wfRunHooks( 'smwShowFactbox', array( &$text, $semdata ) ) ) {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$subjectDv = SMWDataValueFactory::newDataItemValue( $semdata->getSubject(), null );
			SMWOutputs::requireResource( 'ext.smw.style' );
			$rdflink = SMWInfolink::newInternalLink(
				wfMsgForContent( 'smw_viewasrdf' ),
				$wgContLang->getNsText( NS_SPECIAL ) . ':ExportRDF/' .
					$subjectDv->getWikiValue(),
				'rdflink'
			);

			$browselink = SMWInfolink::newBrowsingLink(
				$subjectDv->getText(),
				$subjectDv->getWikiValue(),
				'swmfactboxheadbrowse'
			);
			$text .= '<div class="smwfact">' .
						'<span class="smwfactboxhead">' . wfMsgForContent( 'smw_factbox_head', $browselink->getWikiText() ) . '</span>' .
					'<span class="smwrdflink">' . $rdflink->getWikiText() . '</span>' .
					'<table class="smwfacttable">' . "\n";
			
			foreach ( $semdata->getProperties() as $propertyDi ) {
				$propertyDv = SMWDataValueFactory::newDataItemValue( $propertyDi, null );
				if ( !$propertyDi->isShown() ) { // showing this is not desired, hide
					continue;
				} elseif ( $propertyDi->isUserDefined() ) { // user defined property
					$propertyDv->setCaption( preg_replace( '/[ ]/u', '&#160;', $propertyDv->getWikiValue(), 2 ) );
					/// NOTE: the preg_replace is a slight hack to ensure that the left column does not get too narrow
					$text .= '<tr><td class="smwpropname">' . $propertyDv->getLongWikiText( true ) . '</td><td class="smwprops">';
				} elseif ( $propertyDv->isVisible() ) { // predefined property
					$text .= '<tr><td class="smwspecname">' . $propertyDv->getLongWikiText( true ) . '</td><td class="smwspecs">';
				} else { // predefined, internal property
					continue;
				}

				$propvalues = $semdata->getPropertyValues( $propertyDi );
				
				$valuesHtml = array();
				
				foreach ( $propvalues as $dataItem ) {
					$dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $propertyDi );
					
					if ( $dataValue->isValid() ) {
						$valuesHtml[] = $dataValue->getLongWikiText( true ) . $dataValue->getInfolinkText( SMW_OUTPUT_WIKI );
					}
				}
				
				$text .= $GLOBALS['wgLang']->listToText( $valuesHtml );
				
				$text .= '</td></tr>';
			}
			
			$text .= '</table></div>';
		}
		wfProfileOut( 'SMWFactbox::printFactbox (SMW)' );
		return $text;
	}

	/**
	 * This function creates wiki text suitable for rendering a Factbox based on the
	 * information found in a given ParserOutput object. If the required custom data
	 * is not found in the given ParserOutput, then semantic data for the provided Title
	 * object is retreived from the store.
	 */
	static public function getFactboxTextFromOutput( $parseroutput, $title ) {
		global $wgRequest, $smwgShowFactboxEdit, $smwgShowFactbox;
		$mws =  ( isset( $parseroutput->mSMWMagicWords ) ) ? $parseroutput->mSMWMagicWords : array();
		if ( in_array( 'SMW_SHOWFACTBOX', $mws ) ) {
			$showfactbox = SMW_FACTBOX_NONEMPTY;
		} elseif ( in_array( 'SMW_NOFACTBOX', $mws ) ) {
			$showfactbox = SMW_FACTBOX_HIDDEN;
		} elseif ( $wgRequest->getCheck( 'wpPreview' ) ) {
			$showfactbox = $smwgShowFactboxEdit;
		} else {
			$showfactbox = $smwgShowFactbox;
		}
		if ( $showfactbox == SMW_FACTBOX_HIDDEN ) { // use shortcut
			return '';
		}
		// Deal with complete dataset only if needed:
		if ( !isset( $parseroutput->mSMWData ) || $parseroutput->mSMWData->stubObject ) {
			$semdata = smwfGetStore()->getSemanticData( SMWDIWikiPage::newFromTitle( $title ) );
		} else {
			$semdata = $parseroutput->mSMWData;
		}
		
		return SMWFactbox::getFactboxText( $semdata, $showfactbox );
	}

	/**
	 * This hook copies SMW's custom data from the given ParserOutput object to
	 * the given OutputPage object, since otherwise it is not possible to access
	 * it later on to build a Factbox.
	 */
	static public function onOutputPageParserOutput( $outputpage, $parseroutput ) {
		global $wgTitle, $wgParser;
		$factbox = SMWFactbox::getFactboxTextFromOutput( $parseroutput, $wgTitle );
		
		if ( $factbox != '' ) {
			$popts = new ParserOptions();
			$po = $wgParser->parse( $factbox, $wgTitle, $popts );
			$outputpage->mSMWFactboxText = $po->getText();
			// do not forget to grab the outputs header items
			SMWOutputs::requireFromParserOutput( $po );
			SMWOutputs::commitToOutputPage( $outputpage );
		} // else: nothing shown, don't even set any text
		
		return true;
	}

	/**
	 * This hook is used for inserting the Factbox text directly after the wiki page.
	 */
	static public function onOutputPageBeforeHTML( $outputpage, &$text ) {
		if ( isset( $outputpage->mSMWFactboxText ) ) {
			$text .= $outputpage->mSMWFactboxText;
		}
		return true;
	}

	/**
	 * This hook is used for inserting the Factbox text after the article contents (including
	 * categories).
	 */
	static public function onSkinAfterContent( &$data, $skin = null ) {
		global $wgOut;
		if ( isset( $wgOut->mSMWFactboxText ) ) {
			$data .= $wgOut->mSMWFactboxText;
		}
		return true;
	}

}