<?php

/**
 * This special page for MediaWiki provides information about types. Type information is 
 * stored in the smw_attributes database table, gathered both from the annotations in
 * articles, and from metadata already some global variables managed by SMWTypeHandlerFactory,
 * and in Type: Wiki pages. This only reports on the Type: Wiki pages.
 * 
 * @file SMW_SpecialTypes.php
 * 
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 * 
 * @todo The messages 'smw_isnotype' and 'smw_typeunits', maybe 'smw_isaliastype', could be obsolete now.
 *
 * @author Markus Krötzsch
 */
class SMWSpecialTypes extends SpecialPage {
	
	public function __construct() {
		parent::__construct( 'Types' );
	}

	public function execute( $param ) {
		global $wgOut;
		wfProfileIn( 'smwfDoSpecialTypes (SMW)' );

		$params = SMWInfolink::decodeParameters( $param, false );
		$typeLabel = reset( $params );

		if ( $typeLabel == false ) {
			$wgOut->setPageTitle( wfMsg( 'types' ) );
			$html = $this->getTypesList();
		} else {
			$typeName = str_replace( '_', ' ', $typeLabel );
			$wgOut->setPageTitle( $typeName ); // Maybe add a better message for this
			$html = $this->getTypeProperties( $typeLabel );
		}

		$wgOut->addHTML( $html );
		SMWOutputs::commitToOutputPage( $wgOut );

		wfProfileOut( 'smwfDoSpecialTypes (SMW)' );	
	}

	protected function getTypesList() {
		$html = '<p>' . htmlspecialchars( wfMsg( 'smw_types_docu' ) ) . "</p><br />\n";

		$typeLabels = SMWDataValueFactory::getKnownTypeLabels();
		asort( $typeLabels, SORT_STRING );

		$html .= "<ul>\n";
		foreach ( $typeLabels as $typeId => $label ) {
			$typeValue = SMWTypesValue::newFromTypeId( $typeId );
			$html .= '<li>' . $typeValue->getLongHTMLText( smwfGetLinker() ) . "</li>\n";
		}
		$html .= "</ul>\n";

		return $html;
	}

	protected function getTypeProperties( $typeLabel ) {
		global $wgRequest, $smwgTypePagingLimit;

		if ( $smwgTypePagingLimit <= 0 ) return ''; // not too useful, but we comply to this request

		$from = $wgRequest->getVal( 'from' );
		$until = $wgRequest->getVal( 'until' );
		$typeValue = SMWDataValueFactory::newTypeIDValue( '__typ', $typeLabel );

		$store = smwfGetStore();
		$options = SMWPageLister::getRequestOptions( $smwgTypePagingLimit, $from, $until );
		$diWikiPages = $store->getPropertySubjects( new SMWDIProperty( '_TYPE' ), $typeValue->getDataItem(), $options );
		if ( !$options->ascending ) {
			$diWikiPages = array_reverse( $diWikiPages );
		}

		$result = '';

		if ( count( $diWikiPages ) > 0 ) {
			$pageLister = new SMWPageLister( $diWikiPages, null, $smwgTypePagingLimit, $from, $until );

			$title = $this->getTitleFor( 'Types', $typeLabel );
			$title->setFragment( '#SMWResults' ); // Make navigation point to the result list.
			$navigation = $pageLister->getNavigationLinks( $title );

			$resultNumber = min( $smwgTypePagingLimit, count( $diWikiPages ) );
			$typeName = $typeValue->getLongWikiText();

			$result .= "<a name=\"SMWResults\"></a><div id=\"mw-pages\">\n" .
			           '<h2>' . wfMsg( 'smw_type_header', $typeName ) . "</h2>\n<p>" .
			           wfMsgExt( 'smw_typearticlecount', array( 'parsemag' ), $resultNumber ) . "</p>\n" .
			           $navigation . $pageLister->formatList() . $navigation . "\n</div>";
		}

		return $result;
	}
	
}
