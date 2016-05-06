<?php

use SMW\ApplicationFactory;
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
			$typeLabel = str_replace( '%', '-', $typeLabel );
			$typeName = str_replace( '_', ' ', $typeLabel );
			$wgOut->setPageTitle( $typeName ); // Maybe add a better message for this
			$html = $this->getTypeProperties( $typeLabel );
		}

		$wgOut->addHTML( $html );
		SMWOutputs::commitToOutputPage( $wgOut );

	}

	protected function getTypesList() {

		$typeLabels = DataTypeRegistry::getInstance()->getKnownTypeLabels();
		asort( $typeLabels, SORT_STRING );

		$mwCollaboratorFactory = ApplicationFactory::getInstance()->newMwCollaboratorFactory();
		$htmlColumnListRenderer = $mwCollaboratorFactory->newHtmlColumnListRenderer();

		foreach ( $typeLabels as $typeId => $label ) {
			$typeValue = SMWTypesValue::newFromTypeId( $typeId );
			$startChar = $this->getLanguage()->convert( $this->getLanguage()->firstChar( $typeValue->getWikiValue() ) );
			$contentsByIndex[] = $typeValue->getLongHTMLText( smwfGetLinker() );
		}


		$htmlColumnListRenderer->setNumberOfColumns( 2 );
		$htmlColumnListRenderer->addContentsByNoIndex( $contentsByIndex );
		$htmlColumnListRenderer->setColumnListClass( 'smw-sp-types-list' );

		$html = \Html::rawElement(
			'p',
			array( 'class' => 'smw-sp-types-intro' ),
			wfMessage( 'smw_types_docu' )->parse()
		).  \Html::element(
			'h2',
			array(),
			wfMessage( 'smw-sp-types-list' )->escaped()
		);

		return $html . $htmlColumnListRenderer->getHtml();
	}

	protected function getTypeProperties( $typeLabel ) {
		global $wgRequest, $smwgTypePagingLimit;

		if ( $smwgTypePagingLimit <= 0 ) {
			return ''; // not too useful, but we comply to this request
		}

		$from = $wgRequest->getVal( 'from' );
		$until = $wgRequest->getVal( 'until' );
		$typeValue = DataValueFactory::getInstance()->newTypeIDValue( '__typ', $typeLabel );

		$this->getOutput()->prependHTML( $this->getTypesLink() );

		if ( !$typeValue->isValid() ) {
			return $this->msg( 'smw-special-types-no-such-type' )->escaped();
		}

		$store = \SMW\StoreFactory::getStore();
		$options = SMWPageLister::getRequestOptions( $smwgTypePagingLimit, $from, $until );
		$diWikiPages = $store->getPropertySubjects( new SMW\DIProperty( '_TYPE' ), $typeValue->getDataItem(), $options );

		if ( !$options->ascending ) {
			$diWikiPages = array_reverse( $diWikiPages );
		}

		$escapedTypeLabel = htmlspecialchars( $typeValue->getWikiValue() );

		$canonicalLabel =  DataTypeRegistry::getInstance()->findCanonicalLabelById(
			$typeValue->getDataItem()->getFragment()
		);

		$typeKey  = 'smw-sp-types' . strtolower( $typeValue->getDataItem()->getFragment() );

		$messageKey = wfMessage( $typeKey )->exists() ? $typeKey : 'smw-sp-types-default';

		$result = \Html::rawElement(
			'div',
			array( 'class' => 'smw-sp-types-intro'. $typeKey ),
			wfMessage( $messageKey, str_replace( '_', ' ', $escapedTypeLabel ) )->parse() . ' ' .
			wfMessage( 'smw-sp-types-help', str_replace( ' ', '_', $canonicalLabel ) )->parse()
		);

		$result .= $this->displayExtraInformationAbout( $typeValue );

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

	protected function getGroupName() {
		return 'pages';
	}

	private function displayExtraInformationAbout( $typeValue ) {

		$html = '';

		$dataValue = DataValueFactory::getInstance()->newTypeIDValue(
			$typeValue->getDataItem()->getFragment()
		);

		$escapedTypeLabel = htmlspecialchars( $typeValue->getWikiValue() );

		if ( $typeValue->getDataItem()->getFragment() === '_geo' ) {
			if ( $dataValue instanceof \SMWErrorValue ) {
				$html = \Html::rawElement(
					'p',
					array(),
					wfMessage( 'smw-sp-types-geo-not-available', $escapedTypeLabel )->parse()
				);
			}
		}

		if ( $typeValue->getDataItem()->getFragment() === '_mlt_rec' ) {
			$html = \Html::rawElement(
				'p',
				array(),
				wfMessage( 'smw-sp-types-mlt-lcode', $escapedTypeLabel, ( $dataValue->isEnabledFeature( SMW_DV_MLTV_LCODE ) ? 1 : 2 ) )->parse()
			);
		}

		return $html;
	}

	private function getTypesLink() {
		return \Html::rawElement(
			'div',
			array( 'class' => 'smw-breadcrumb-link' ),
			\Html::rawElement(
				'span',
				array( 'class' => 'smw-breadcrumb-arrow-right' ),
				''
			) .
			\Html::rawElement(
				'a',
				array( 'href' => \SpecialPage::getTitleFor( 'Types')->getFullURL() ),
				$this->msg( 'types' )->escaped()
		) );
	}

}
