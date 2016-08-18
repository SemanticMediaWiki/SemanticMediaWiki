<?php

namespace SMW\MediaWiki\Specials;

use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Localizer;
use SMW\SemanticData;
use SMW\UrlEncoder;
use SMW\MediaWiki\Specials\Browse\HtmlContentBuilder;
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

		// @see SMWInfolink::encodeParameters
		if ( $query === null && $this->getRequest()->getCheck( 'x' ) ) {
			$query = $this->getRequest()->getVal( 'x' );
		}

		// no GET parameters? Then try the URL
		if ( $articletext === null ) {
			$articletext = UrlEncoder::decode( $query );
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
			$this->getHtml( $webRequest )
		);

		$this->addExternalHelpLinks();
	}

	private function getHtml( $webRequest ) {

		if ( !$this->subjectDV->isValid() ) {
			return Html::rawElement(
					'div',
					array(
						'class' => 'smw-callout smw-callout-error'
					),
					Message::get( array( 'smw-browse-subject-invalid', $this->subjectDV->getErrors() ) )
				) . HtmlContentBuilder::getPageSearchQuickForm();
		}

		$htmlContentBuilder = $this->newHtmlContentBuilder( $webRequest );

		if ( $webRequest->getVal( 'output' ) === 'legacy' || !$htmlContentBuilder->getOption( 'byApi' ) ) {
			return $htmlContentBuilder->getHtml();
		}

		$options = array(
			'dir'         => $htmlContentBuilder->getOption( 'dir' ),
			'offset'      => $htmlContentBuilder->getOption( 'offset' ),
			'printable'   => $htmlContentBuilder->getOption( 'printable' ),
			'showInverse' => $htmlContentBuilder->getOption( 'showInverse' ),
			'showAll'     => $htmlContentBuilder->getOption( 'showAll' )
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
				)
			) . Html::rawElement(
				'div',
				array(
					'class' => 'smwb-content is-disabled'
				),
				Html::rawElement(
					'span',
					array(
						'class' => 'spinner large inline'
					)
				) . $htmlContentBuilder->getEmptyHtml()
			)
		);

		return $html;
	}

	private function newHtmlContentBuilder( $webRequest ) {

		$htmlContentBuilder = new HtmlContentBuilder(
			$this->applicationFactory->getStore(),
			$this->subjectDV->getDataItem()
		);

		$htmlContentBuilder->setOption(
			'dir',
			$webRequest->getVal( 'dir' )
		);

		$htmlContentBuilder->setOption(
			'printable',
			$webRequest->getVal( 'printable' )
		);

		$htmlContentBuilder->setOption(
			'offset',
			$webRequest->getVal( 'offset' )
		);

		$htmlContentBuilder->setOption(
			'showInverse',
			$this->applicationFactory->getSettings()->get( 'smwgBrowseShowInverse' )
		);

		$htmlContentBuilder->setOption(
			'showAll',
			$this->applicationFactory->getSettings()->get( 'smwgBrowseShowAll' )
		);

		$htmlContentBuilder->setOption(
			'byApi',
			$this->applicationFactory->getSettings()->get( 'smwgBrowseByApi' )
		);

		return $htmlContentBuilder;
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
				Html::rawElement(
					'div',
					array(
						'class' => 'mw-indicator smw-page-indicator-rdflink'
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
