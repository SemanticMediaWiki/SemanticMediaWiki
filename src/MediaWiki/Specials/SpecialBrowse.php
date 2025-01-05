<?php

namespace SMW\MediaWiki\Specials;

use Html;
use SMW\DataValueFactory;
use SMW\Encoder;
use SMW\MediaWiki\Specials\Browse\FieldBuilder;
use SMW\MediaWiki\Specials\Browse\HtmlBuilder;
use SMW\Message;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMWInfolink as Infolink;
use SpecialPage;
use TemplateParser;

/**
 * A factbox view on one specific article, showing all the Semantic data about it
 *
 * @license GPL-2.0-or-later
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
			$query = Infolink::decodeCompactLink( 'cl:' . $webRequest->getText( 'cl' ) );
		} else {
			$query = Infolink::decodeCompactLink( $query );
		}

		$isEmptyRequest = $query === null && ( $webRequest->getVal( 'article' ) === '' || $webRequest->getVal( 'article' ) === null );

		// @see SMWInfolink::encodeParameters
		if ( $query === null && $this->getRequest()->getCheck( 'x' ) ) {
			$query = $this->getRequest()->getVal( 'x' );
		}

		// Auto-generated link is marked with a leading :
		if ( is_string( $query ) && $query !== '' && $query[0] === ':' ) {
			$articletext = Encoder::unescape( $query );
		} elseif ( $articletext === null ) {
			$articletext = $query;
		}

		$dataValue = DataValueFactory::getInstance()->newTypeIDValue(
			'_wpg',
			$articletext ?? false
		);

		$out = $this->getOutput();
		$out->setHTMLTitle( $dataValue->getWikiValue() );

		$out->addModuleStyles( [
			'mediawiki.ui',
			'mediawiki.ui.button',
			'mediawiki.ui.input',
			'ext.smw.factbox.styles',
			'ext.smw.browse.styles'
		] );

		$out->addModules( [
			'ext.smw.browse',
			'ext.smw.tooltip'
		] );

		$templateParser = new TemplateParser( __DIR__ . '/../../../templates' );
		$data = $this->getTemplateData( $webRequest, $dataValue, $isEmptyRequest );
		$out->addHTML( $templateParser->processTemplate( 'SpecialBrowse', $data ) );

		/** @todo Move RDF link into factbox like how bottom factboxes are */
		$this->addExternalHelpLinks( $dataValue );
	}

	private function getTemplateData( $webRequest, $dataValue, $isEmptyRequest ): array {
		$data = [];
		if ( $isEmptyRequest && !$this->including() ) {
			$data['html-output'] = Message::get( 'smw-browse-intro', Message::TEXT, Message::USER_LANGUAGE );
			$data['data-form'] = FieldBuilder::getQueryFormData();
			return $data;
		}

		if ( !$dataValue->isValid() ) {
			$error = '';
			foreach ( $dataValue->getErrors() as $err ) {
				$error .= Message::decode( $err, Message::TEXT, Message::USER_LANGUAGE );
			}
			$data['html-output'] = Html::errorBox(
				Message::get( [ 'smw-browse-invalid-subject', $error ], Message::TEXT, Message::USER_LANGUAGE ),
				'',
				'smw-error-browse'
			);
			if ( !$this->including() ) {
				$data['data-form'] = FieldBuilder::getQueryFormData( $webRequest->getVal( 'article', '' ) );
			}
			return $data;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$dataItem = $dataValue->getDataItem();

		$htmlBuilder = $this->newHtmlBuilder(
			$webRequest,
			$dataItem,
			$applicationFactory->getStore(),
			$applicationFactory->getSettings()
		);

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
			$data['html-output'] = $htmlBuilder->legacy();
			return $data;
		}

		// Ajax/API is doing the data fetch
		return $htmlBuilder->getPlaceholderData();
	}

	private function newHtmlBuilder( $webRequest, $dataItem, $store, $settings ) {
		$htmlBuilder = new HtmlBuilder(
			$store,
			$dataItem
		);

		$htmlBuilder->setOptions(
			[
				'dir' => $webRequest->getVal( 'dir' ),
				'lang' => $webRequest->getVal( 'lang', $this->getLanguage()->getCode() ),
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

		$this->addHelpLink( $this->msg( 'smw-specials-browse-helplink' )->escaped(), true );
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group/search';
	}

}
