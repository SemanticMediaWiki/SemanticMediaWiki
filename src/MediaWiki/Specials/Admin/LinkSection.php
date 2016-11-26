<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Renderer\HtmlFormRenderer;
use SMW\Message;
use SMW\NamespaceManager;
use Html;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class LinkSection {

	/**
	 * @var HtmlFormRenderer
	 */
	private $htmlFormRenderer;

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @since 2.5
	 *
	 * @param HtmlFormRenderer $htmlFormRenderer
	 */
	public function __construct( HtmlFormRenderer $htmlFormRenderer, OutputFormatter $outputFormatter ) {
		$this->htmlFormRenderer = $htmlFormRenderer;
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getForm() {
		$html =	Html::rawElement( 'h2', array(), $this->getMessage( array( 'smw-smwadmin-information-section-title' ) ) );
		$html .= Html::rawElement( 'p', array(), $this->getMessage( array( 'smw-smwadmin-information-section-intro' ) ) );
		$html .= Html::rawElement( 'div', array( 'class' => 'smw-admin-supplementary-linksection' ),
			Html::rawElement( 'ul', array(),
				Html::rawElement(
					'li',
					array(),
					$this->getMessage( array( 'smw-smwadmin-operational-statistics-intro', $this->outputFormatter->getSpecialPageLinkWith( $this->getMessage( 'smw-smwadmin-operational-statistics-title' ), array( 'action' => 'stats' ) ) ) )
				) .
				Html::rawElement(
					'li',
					array(),
					$this->getMessage( array( 'smw-smwadmin-settings-intro', $this->outputFormatter->getSpecialPageLinkWith( $this->getMessage( 'smw-smwadmin-settings-title' ), array( 'action' => 'settings' ) ) ) )
				) .
				Html::rawElement(
					'li',
					array(),
					$this->getMessage( array( 'smw-smwadmin-idlookup-intro', $this->outputFormatter->getSpecialPageLinkWith( $this->getMessage( 'smw-smwadmin-idlookup-title' ), array( 'action' => 'idlookup' ) ) ) )
				)
			)
		);

		return $html . Html::element( 'p', array(), '' );
	}

	/**
	 * @since 2.5
	 */
	public function outputConfigurationList() {

		$this->outputFormatter->setPageTitle( $this->getMessage( 'smw-smwadmin-settings-title' ) );
		$this->outputFormatter->addParentLink();

		$this->outputFormatter->addHtml(
			Html::rawElement( 'p', array(), $this->getMessage( 'smw-sp-admin-settings-docu', Message::PARSE ) )
		);

		$this->outputFormatter->addHtml(
			'<pre>' . $this->outputFormatter->encodeAsJson( ApplicationFactory::getInstance()->getSettings()->getOptions() ) . '</pre>'
		);

		$this->outputFormatter->addHtml(
			'<pre>' . $this->outputFormatter->encodeAsJson( array( 'canonicalNames' => NamespaceManager::getCanonicalNames() ) ) . '</pre>'
		);
	}

	/**
	 * @since 2.5
	 */
	public function outputStatistics() {

		$this->outputFormatter->setPageTitle( $this->getMessage( 'smw-smwadmin-operational-statistics-title' ) );
		$this->outputFormatter->addParentLink();

		$semanticStatistics = ApplicationFactory::getInstance()->getStore()->getStatistics();

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), $this->getMessage( array( 'smw-smwadmin-operational-statistics' ), Message::PARSE ) )
		);

		$this->outputFormatter->addHTML(
			Html::element( 'h2', array(), $this->getMessage( 'semanticstatistics' ) )
		);

		$this->outputFormatter->addHTML( '<pre>' . $this->outputFormatter->encodeAsJson(
			array(
				'propertyValues' => $semanticStatistics['PROPUSES'],
				'errorCount' => $semanticStatistics['ERRORUSES'],
				'propertyTotal' => $semanticStatistics['USEDPROPS'],
				'ownPage' => $semanticStatistics['OWNPAGE'],
				'declaredType' => $semanticStatistics['DECLPROPS'],
				'oudatedEntities' => $semanticStatistics['DELETECOUNT'],
				'subobjects' => $semanticStatistics['SUBOBJECTS'],
				'queries' => $semanticStatistics['QUERY'],
				'concepts' => $semanticStatistics['CONCEPTS'],
			) ) . '</pre>'
		);

		$this->outputFormatter->addHTML(
			Html::element( 'h2', array(),  $this->getMessage( 'smw-smwadmin-statistics-querycache-title' ) )
		);

		$cachedQueryResultPrefetcher = ApplicationFactory::getInstance()->singleton( 'CachedQueryResultPrefetcher' );

		if ( !$cachedQueryResultPrefetcher->isEnabled() ) {
			return $this->outputFormatter->addHTML(
				Html::rawElement( 'p', array(), $this->getMessage( array( 'smw-smwadmin-statistics-querycache-disabled' ), Message::PARSE ) )
			);
		}

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), $this->getMessage( array( 'smw-smwadmin-statistics-querycache-explain' ), Message::PARSE ) )
		);

		$this->outputFormatter->addHTML( '<pre>' . $this->outputFormatter->encodeAsJson( $cachedQueryResultPrefetcher->getStats() ) . '</pre>' );
	}

	private function getMessage( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

}
