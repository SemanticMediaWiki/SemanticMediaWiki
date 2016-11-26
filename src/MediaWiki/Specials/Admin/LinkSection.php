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
		$html =	Html::rawElement( 'h2', array(), Message::get( array( 'smw-smwadmin-information-section-title' ), Message::TEXT, Message::USER_LANGUAGE ) );
		$html .= Html::rawElement( 'p', array(), Message::get( array( 'smw-smwadmin-information-section-intro' ), Message::TEXT, Message::USER_LANGUAGE ) );
		$html .= Html::rawElement( 'div', array( 'class' => 'smw-column-twofold-responsive' ),
			Html::rawElement( 'ul', array(),
				Html::rawElement(
					'li',
					array(),
					Message::get( array( 'smw-smwadmin-operational-statistics-intro', $this->outputFormatter->getSpecialPageLinkWith( Message::get( 'smw-smwadmin-operational-statistics-title' ), array( 'action' => 'stats' ) ) ), Message::TEXT, Message::USER_LANGUAGE )
				) .
				Html::rawElement(
					'li',
					array(),
					Message::get( array( 'smw-smwadmin-settings-intro', $this->outputFormatter->getSpecialPageLinkWith( Message::get( 'smw-smwadmin-settings-title' ), array( 'action' => 'settings' ) ) ), Message::TEXT, Message::USER_LANGUAGE )
				) .
				Html::rawElement(
					'li',
					array(),
					Message::get( array( 'smw-smwadmin-idlookup-intro', $this->outputFormatter->getSpecialPageLinkWith( Message::get( 'smw-smwadmin-idlookup-title' ), array( 'action' => 'idlookup' ) ) ), Message::TEXT, Message::USER_LANGUAGE )
				)
			)
		);

		return $html . Html::element( 'p', array(), '' );
	}

	/**
	 * @since 2.5
	 */
	public function outputConfigurationList() {

		$this->outputFormatter->setPageTitle( Message::get( 'smw-smwadmin-settings-title', Message::TEXT, Message::USER_LANGUAGE ) );
		$this->outputFormatter->addParentLink();

		$this->outputFormatter->addHtml(
			Html::rawElement( 'p', array(), Message::get( 'smw-sp-admin-settings-docu', Message::PARSE, Message::USER_LANGUAGE ) )
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

		$this->outputFormatter->setPageTitle( 'Statistics' );
		$this->outputFormatter->addParentLink();

		$semanticStatistics = ApplicationFactory::getInstance()->getStore()->getStatistics();

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), Message::get( array( 'smw-smwadmin-operational-statistics' ), Message::PARSE, Message::USER_LANGUAGE ) )
		);

		$this->outputFormatter->addHTML(
			Html::element( 'h2', array(),  Message::get( 'semanticstatistics', Message::TEXT, Message::USER_LANGUAGE ) )
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
			Html::element( 'h2', array(),  Message::get( 'smw-smwadmin-statistics-querycache-title', Message::TEXT, Message::USER_LANGUAGE ) )
		);

		$cachedQueryResultPrefetcher = ApplicationFactory::getInstance()->singleton( 'CachedQueryResultPrefetcher' );

		if ( !$cachedQueryResultPrefetcher->isEnabled() ) {
			return $this->outputFormatter->addHTML(
				Html::rawElement( 'p', array(), Message::get( array( 'smw-smwadmin-statistics-querycache-disabled' ), Message::PARSE, Message::USER_LANGUAGE ) )
			);
		}

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), Message::get( array( 'smw-smwadmin-statistics-querycache-explain' ), Message::PARSE, Message::USER_LANGUAGE ) )
		);

		$this->outputFormatter->addHTML( '<pre>' . $this->outputFormatter->encodeAsJson( $cachedQueryResultPrefetcher->getStats() ) . '</pre>' );

	}

}
