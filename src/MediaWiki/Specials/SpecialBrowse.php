<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Localizer;
use SMW\SemanticData;
use SMW\UrlEncoder;
use SMW\MediaWiki\Specials\Browse\HtmlContentsBuilder;
use SMW\Message;
use SpecialPage;
use Html;

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
	 * @var DataValue
	 */
	private $subjectDV = null;

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @see SpecialPage::__construct
	 */
	public function __construct() {
		parent::__construct( 'Browse', '', true, false, 'default', true );
		$this->applicationFactory = ApplicationFactory::getInstance();
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
		$isEmptyRequest = $query === null && ( $webRequest->getVal( 'article' ) === '' || $webRequest->getVal( 'article' ) === null );

		// @see SMWInfolink::encodeParameters
		if ( $query === null && $this->getRequest()->getCheck( 'x' ) ) {
			$query = $this->getRequest()->getVal( 'x' );
		}

		// Auto-generated link is marked with a leading :
		if ( $query !== '' && $query{0} === ':' ) {
			$articletext = UrlEncoder::unescape( $query );
		} elseif ( $articletext === null ) {
			$articletext = $query;
		}

		// no GET parameters? Then try the URL
		if ( $articletext === null ) {
		}

		$this->subjectDV = DataValueFactory::getInstance()->newTypeIDValue(
			'_wpg',
			$articletext
		);

		$out = $this->getOutput();
		$out->setHTMLTitle( $this->subjectDV->getTitle() );

		$out->addModuleStyles( array(
			'mediawiki.ui',
			'mediawiki.ui.button',
			'mediawiki.ui.input'
		) );

		$out->addModules( array(
			'ext.smw.browse',
			'ext.smw.tooltip'
		) );

		$out->addHTML(
			$this->getHtml( $webRequest, $isEmptyRequest )
		);

		$this->addExternalHelpLinks();
	}

	private function getHtml( $webRequest, $isEmptyRequest ) {

		if ( $isEmptyRequest && !$this->including() ) {
			return HtmlContentsBuilder::getPageSearchQuickForm();
		}

		if ( !$this->subjectDV->isValid() ) {

			foreach ( $this->subjectDV->getErrors() as $error ) {
				$error = Message::decode( $error );
			}

			$html = Html::rawElement(
				'div',
				array(
					'class' => 'smw-callout smw-callout-error'
				),
				Message::get( array( 'smw-browse-invalid-subject', $error ), Message::ESCAPED )
			);

			if ( !$this->including() ) {
				$html . HtmlContentsBuilder::getPageSearchQuickForm();
			}

			return $html;
		}

		$htmlContentsBuilder = $this->newHtmlContentsBuilder(
			$webRequest,
			$this->applicationFactory->getSettings()
		);

		if ( $webRequest->getVal( 'output' ) === 'legacy' || !$htmlContentsBuilder->getOption( 'byApi' ) ) {
			return $htmlContentsBuilder->getHtml();
		}

		$options = array(
			'dir'         => $htmlContentsBuilder->getOption( 'dir' ),
			'offset'      => $htmlContentsBuilder->getOption( 'offset' ),
			'printable'   => $htmlContentsBuilder->getOption( 'printable' ),
			'showInverse' => $htmlContentsBuilder->getOption( 'showInverse' ),
			'showAll'     => $htmlContentsBuilder->getOption( 'showAll' ),
			'including'   => $htmlContentsBuilder->getOption( 'including' )
		);

		// Ajax/API is doing the data fetch
		$html = Html::rawElement(
			'div',
			array(
				'class' => 'smwb-container',
				'data-subject' => $this->subjectDV->getDataItem()->getHash(),
				'data-options' => json_encode( $options )
			),
			Html::rawElement(
				'div',
				array(
					'class' => 'smwb-status'
				),
				Html::rawElement(
					'noscript',
					array(),
					Html::rawElement(
						'div',
						array(
							'class' => 'smw-callout smw-callout-error',
						),
						Message::get( 'smw-browse-js-disabled', Message::PARSE )
					)
				)
			) . Html::rawElement(
				'div',
				array(
					'class' => 'smwb-content is-disabled'
				),
				Html::rawElement(
					'span',
					array(
						'class' => 'smw-overlay-spinner large inline'
					)
				) . $htmlContentsBuilder->getEmptyHtml()
			)
		);

		return $html;
	}

	private function newHtmlContentsBuilder( $webRequest, $settings ) {

		$htmlContentsBuilder = new HtmlContentsBuilder(
			$this->applicationFactory->getStore(),
			$this->subjectDV->getDataItem()
		);

		$htmlContentsBuilder->setOption(
			'dir',
			$webRequest->getVal( 'dir' )
		);

		$htmlContentsBuilder->setOption(
			'printable',
			$webRequest->getVal( 'printable' )
		);

		$htmlContentsBuilder->setOption(
			'offset',
			$webRequest->getVal( 'offset' )
		);

		$htmlContentsBuilder->setOption(
			'including',
			$this->including()
		);

		$htmlContentsBuilder->setOption(
			'showInverse',
			$settings->get( 'smwgBrowseShowInverse' )
		);

		$htmlContentsBuilder->setOption(
			'showAll',
			$settings->get( 'smwgBrowseShowAll' )
		);

		$htmlContentsBuilder->setOption(
			'byApi',
			$settings->get( 'smwgBrowseByApi' )
		);

		return $htmlContentsBuilder;
	}

	private function addExternalHelpLinks() {

		if ( $this->getRequest()->getVal( 'printable' ) === 'yes' ) {
			return null;
		}

		// FIXME with SMW 3.0, allow to be used with MW 1.25-
		if ( !method_exists( $this, 'addHelpLink' ) ) {
			return null;
		}

		if ( $this->subjectDV->isValid() ) {
			$link = SpecialPage::getTitleFor( 'ExportRDF', $this->subjectDV->getTitle()->getPrefixedText() );

			$this->getOutput()->setIndicators( array(
				'browse' => Html::rawElement(
					'div',
					array(
						'class' => 'smw-page-indicator-rdflink'
					),
					Html::rawElement(
						'a',
						array(
							'href' => $link->getLocalUrl( 'syntax=rdf' )
						),
						'RDF'
					)
				)
			) );
		}

		$this->addHelpLink( wfMessage( 'smw-specials-browse-helplink' )->escaped(), true );
	}

	protected function getGroupName() {
		return 'smw_group';
	}

}
