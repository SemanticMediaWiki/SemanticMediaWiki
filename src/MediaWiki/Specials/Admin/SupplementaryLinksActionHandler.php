<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\Message;
use SMW\NamespaceManager;
use Html;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class SupplementaryLinksActionHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @since 2.5
	 *
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( OutputFormatter $outputFormatter ) {
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 2.5
	 */
	public function doOutputConfigurationList() {

		$this->outputFormatter->setPageTitle( $this->getMessage( 'smw-admin-supplementary-settings-title' ) );
		$this->outputFormatter->addParentLink();

		$this->outputFormatter->addHtml(
			Html::rawElement( 'p', array(), $this->getMessage( 'smw-admin-settings-docu', Message::PARSE ) )
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
	public function doOutputStatistics() {

		$this->outputFormatter->setPageTitle( $this->getMessage( 'smw-admin-supplementary-operational-statistics-title' ) );
		$this->outputFormatter->addParentLink();

		$this->outputSemanticStatistics();
		$this->outputJobStatistics();
		$this->outputQueryCacheStatistics();
	}

	private function getMessage( $key, $type = Message::TEXT ) {
		return Message::get( $key, $type, Message::USER_LANGUAGE );
	}

	private function outputSemanticStatistics() {

		$semanticStatistics = ApplicationFactory::getInstance()->getStore()->getStatistics();

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), $this->getMessage( array( 'smw-admin-operational-statistics' ), Message::PARSE ) )
		);

		$this->outputFormatter->addHTML(
			Html::element( 'h2', array(), $this->getMessage( 'semanticstatistics' ) )
		);

		$this->outputFormatter->addHTML( '<pre>' . $this->outputFormatter->encodeAsJson(
			array(
				'propertyValues' => $semanticStatistics['PROPUSES'],
				'errorCount' => $semanticStatistics['ERRORUSES'],
				'totalProperties' => $semanticStatistics['TOTALPROPS'],
				'usedProperties' => $semanticStatistics['USEDPROPS'],
				'ownPage' => $semanticStatistics['OWNPAGE'],
				'declaredType' => $semanticStatistics['DECLPROPS'],
				'oudatedEntities' => $semanticStatistics['DELETECOUNT'],
				'subobjects' => $semanticStatistics['SUBOBJECTS'],
				'queries' => $semanticStatistics['QUERY'],
				'concepts' => $semanticStatistics['CONCEPTS'],
			) ) . '</pre>'
		);
	}

	private function outputJobStatistics() {

		$this->outputFormatter->addHTML(
			Html::element( 'h2', array(), $this->getMessage( 'smw-admin-statistics-job-title' ) )
		);

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), $this->getMessage( 'smw-admin-statistics-job-docu', Message::PARSE ) )
		);

		$this->outputFormatter->addHTML(
			Html::rawElement(
				'div',
				array(
					'class' => 'smw-admin-statistics-job',
					'data-config' => json_encode( array(
						'contentClass' => 'smw-admin-statistics-job-content',
						'errorClass'   => 'smw-admin-statistics-job-error'
					) ),
				),
				Html::element( 'div', array( 'class' => 'smw-admin-statistics-job-error' ), '' ) .
				Html::element( 'div', array( 'class' => 'smw-admin-statistics-job-content' ), $this->getMessage( 'smw-data-lookup' ) )
			)
		);
	}

	private function outputQueryCacheStatistics() {

		$this->outputFormatter->addHTML(
			Html::element( 'h2', array(),  $this->getMessage( 'smw-admin-statistics-querycache-title' ) )
		);

		$cachedQueryResultPrefetcher = ApplicationFactory::getInstance()->singleton( 'CachedQueryResultPrefetcher' );

		if ( !$cachedQueryResultPrefetcher->isEnabled() ) {
			return $this->outputFormatter->addHTML(
				Html::rawElement( 'p', array(), $this->getMessage( array( 'smw-admin-statistics-querycache-disabled' ), Message::PARSE ) )
			);
		}

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), $this->getMessage( array( 'smw-admin-statistics-querycache-explain' ), Message::PARSE ) )
		);

		$this->outputFormatter->addHTML(
			'<pre>' . $this->outputFormatter->encodeAsJson( $cachedQueryResultPrefetcher->getStats() ) . '</pre>'
		);
	}

}
