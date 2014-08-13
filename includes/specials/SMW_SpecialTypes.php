<?php

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;

/**
 * This special page for MediaWiki provides information about types. Type information is
 * stored in the smw_attributes database table, gathered both from the annotations in
 * articles, and from metadata already some global variables managed by SMWTypeHandlerFactory,
 * and in Type: Wiki pages. This only reports on the Type: Wiki pages.
 *
 *
 * @ingroup SMWSpecialPage
 * @ingroup SpecialPage
 *
 * @author Markus Krötzsch
 */
class SMWSpecialTypes extends SpecialPage {
	public function __construct() {
		parent::__construct( 'Types' );
	}

	public function execute( $param ) {
		global $wgOut;

		$params = SMWInfolink::decodeParameters( $param, false );
		$typeLabel = reset( $params );

		if ( $typeLabel == false ) {
			$wgOut->setPageTitle( wfMessage( 'types' )->text() );
			$html = $this->getTypesList();
		} else {
			$typeName = str_replace( '_', ' ', $typeLabel );
			$wgOut->setPageTitle( $typeName ); // Maybe add a better message for this
			$html = $this->getTypeProperties( $typeLabel );
		}

		$wgOut->addHTML( $html );
		SMWOutputs::commitToOutputPage( $wgOut );

	}

	protected function getTypesList() {
		$html = '<p>' . wfMessage( 'smw_types_docu' )->escaped() . "</p><br />\n";

		$typeLabels = DataTypeRegistry::getInstance()->getKnownTypeLabels();
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
		$typeValue = DataValueFactory::getInstance()->newTypeIDValue( '__typ', $typeLabel );

		if ( !$typeValue->isValid() ) {
			return $this->msg( 'smw-special-types-no-such-type' )->escaped();
		}

		$store = \SMW\StoreFactory::getStore();
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
			        '<h2>' . wfMessage( 'smw_type_header', $typeName )->text() . "</h2>\n<p>" .
					wfMessage( 'smw_typearticlecount' )->numParams( $resultNumber )->text() . "</p>\n" .
			        $navigation . $pageLister->formatList() . $navigation . "\n</div>";
		}

		return $result;
	}
}
