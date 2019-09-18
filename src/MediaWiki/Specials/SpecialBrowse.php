<?php

namespace SMW\MediaWiki\Specials;

use Html;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\Encoder;
use SMW\MediaWiki\Specials\Browse\HtmlBuilder;
use SMW\MediaWiki\Specials\Browse\FieldBuilder;
use SMW\Message;
use SMWInfolink as Infolink;
use SpecialPage;

/**
 * A factbox view on one specific article, showing all the Semantic data about it
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author mwjames
 */
class SpecialBrowse extends SpecialPage {

	/**
	 * @see SpecialPage::__construct
	 */
	public function __construct() {
		parent::__construct( 'Browse', '', true, false, 'default', true );
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string $query string
	 */
	public function execute( $query ) {

		$this->setHeaders();
		$webRequest = $this->getRequest();

		// get the GET parameters
		$articletext = $webRequest->getVal( 'article' );

		if ( $webRequest->getText( 'cl', '' ) !== '' ) {
			$query = Infolink::decodeCompactLink( 'cl:'. $webRequest->getText( 'cl' ) );
		} else {
			$query = Infolink::decodeCompactLink( $query );
		}

		$isEmptyRequest = $query === null && ( $webRequest->getVal( 'article' ) === '' || $webRequest->getVal( 'article' ) === null );

		// @see SMWInfolink::encodeParameters
		if ( $query === null && $this->getRequest()->getCheck( 'x' ) ) {
			$query = $this->getRequest()->getVal( 'x' );
		}

		// Auto-generated link is marked with a leading :
		if ( $query !== '' && $query[0] === ':' ) {
			$articletext = Encoder::unescape( $query );
		} elseif ( $articletext === null ) {
			$articletext = $query;
		}

		// no GET parameters? Then try the URL
		if ( $articletext === null ) {
		}

		$dataValue = DataValueFactory::getInstance()->newTypeIDValue(
			'_wpg',
			$articletext
		);

		$out = $this->getOutput();
		$out->setHTMLTitle( $dataValue->getWikiValue() );

		$out->addModuleStyles( [
			'mediawiki.ui',
			'mediawiki.ui.button',
			'mediawiki.ui.input',
			'ext.smw.browse.styles'
		] );

		$out->addModules( [
			'ext.smw.browse',
			'ext.smw.tooltips'
		] );

		$out->addHTML(
			$this->buildHTML( $webRequest, $dataValue, $isEmptyRequest )
		);

		$this->addExternalHelpLinks( $dataValue );
	}

	private function buildHTML( $webRequest, $dataValue, $isEmptyRequest ) {

		if ( $isEmptyRequest && !$this->including() ) {
			return Message::get( 'smw-browse-intro', Message::TEXT, Message::USER_LANGUAGE ) . FieldBuilder::createQueryForm();
		}

		if ( !$dataValue->isValid() ) {
			$error = '';

			foreach ( $dataValue->getErrors() as $error ) {
				$error .= Message::decode( $error, Message::TEXT, Message::USER_LANGUAGE );
			}

			$html = Html::rawElement(
				'div',
				[
					'class' => 'smw-callout smw-callout-error'
				],
				Message::get( [ 'smw-browse-invalid-subject', $error ], Message::TEXT, Message::USER_LANGUAGE )
			);

			if ( !$this->including() ) {
				$html .= FieldBuilder::createQueryForm( $webRequest->getVal( 'article' ) );
			}

			return $html;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$dataItem = $dataValue->getDataItem();

		$htmlBuilder = $this->newHtmlBuilder(
			$webRequest,
			$dataItem,
			$applicationFactory->getStore(),
			$applicationFactory->getSettings()
		);

		$options = $htmlBuilder->getOptions();

		if ( $webRequest->getVal( 'format' ) === 'json' ) {
			$semanticDataSerializer = $applicationFactory->newSerializerFactory()->newSemanticDataSerializer();
			$res = $semanticDataSerializer->serialize(
				$applicationFactory->getStore()->getSemanticData( $dataItem )
			);

			$this->getOutput()->disable();
			header( 'Content-type: ' . 'application/json' . '; charset=UTF-8' );
			echo json_encode( $res, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		}

		if ( $webRequest->getVal( 'output' ) === 'legacy' || !$htmlBuilder->getOption( 'api' ) ) {
			return $htmlBuilder->legacy();
		}

		// Ajax/API is doing the data fetch
		return $htmlBuilder->placeholder();
	}

	private function newHtmlBuilder( $webRequest, $dataItem, $store, $settings ) {

		$htmlBuilder = new HtmlBuilder(
			$store,
			$dataItem
		);

		$htmlBuilder->setOptions(
			[
				'dir' => $webRequest->getVal( 'dir' ),
				'group' => $webRequest->getVal( 'group' ),
				'printable' => $webRequest->getVal( 'printable' ),
				'offset' => $webRequest->getVal( 'offset' ),
				'including' => $this->including(),
				'showInverse' => $settings->isFlagSet( 'smwgBrowseFeatures', SMW_BROWSE_SHOW_INVERSE ),
				'showAll' => $settings->isFlagSet( 'smwgBrowseFeatures', SMW_BROWSE_SHOW_INCOMING ),
				'showGroup' => $settings->isFlagSet( 'smwgBrowseFeatures', SMW_BROWSE_SHOW_GROUP ),
				'showSort' => $settings->isFlagSet( 'smwgBrowseFeatures', SMW_BROWSE_SHOW_SORTKEY ),
				'api' => $settings->isFlagSet( 'smwgBrowseFeatures', SMW_BROWSE_USE_API ),

				// WebRequest::getGPCVal/getVal doesn't understand `.` as in
				// `valuelistlimit.out`

				'valuelistlimit.out' => $webRequest->getVal(
					'valuelistlimit-out',
					$settings->dotGet( 'smwgPagingLimit.browse.valuelist.outgoing' )
				),
				'valuelistlimit.in' => $webRequest->getVal(
					'valuelistlimit-in',
					$settings->dotGet( 'smwgPagingLimit.browse.valuelist.incoming' )
				),
			]
		);

		return $htmlBuilder;
	}

	private function addExternalHelpLinks( $dataValue ) {

		if ( $this->getRequest()->getVal( 'printable' ) === 'yes' ) {
			return null;
		}

		if ( $dataValue->isValid() ) {
			$dataItem = $dataValue->getDataItem();

			$title = SpecialPage::getTitleFor( 'ExportRDF', $dataItem->getTitle()->getPrefixedText() );

			$this->getOutput()->setIndicators( [
				'browse' => Html::rawElement(
					'div',
					[
						'class' => 'smw-page-indicator-rdflink'
					],
					Html::rawElement(
						'a',
						[
							'href' => $title->getLocalUrl( 'syntax=rdf' )
						],
						'RDF'
					)
				)
			] );
		}

		$this->addHelpLink( wfMessage( 'smw-specials-browse-helplink' )->escaped(), true );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {

		if ( version_compare( MW_VERSION, '1.33', '<' ) ) {
			return 'smw_group';
		}

		// #3711, MW 1.33+
		return 'smw_group/search';
	}

}
