<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\ApplicationFactory;
use SMW\Message;
use SMW\NamespaceManager;
use Html;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   2.5
 *
 * @author mwjames
 */
class OperationalStatisticsListTaskHandler extends TaskHandler {

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
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === 'stats';
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {
		return Html::rawElement(
			'li',
			array(),
			$this->getMessageAsString(
				array(
					'smw-admin-supplementary-operational-statistics-intro',
					$this->outputFormatter->getSpecialPageLinkWith( $this->getMessageAsString( 'smw-admin-supplementary-operational-statistics-title' ), array( 'action' => 'stats' ) )
				)
			)
		);
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		$this->outputFormatter->setPageTitle( $this->getMessageAsString( 'smw-admin-supplementary-operational-statistics-title' ) );
		$this->outputFormatter->addParentLink();

		$this->outputSemanticStatistics();
		$this->outputJobStatistics();
		$this->outputQueryCacheStatistics();
	}

	private function outputSemanticStatistics() {

		$semanticStatistics = ApplicationFactory::getInstance()->getStore()->getStatistics();

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), $this->getMessageAsString( array( 'smw-admin-operational-statistics' ), Message::PARSE ) )
		);

		$this->outputFormatter->addHTML(
			Html::element( 'h2', array(), $this->getMessageAsString( 'semanticstatistics' ) )
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
			Html::element( 'h2', array(), $this->getMessageAsString( 'smw-admin-statistics-job-title' ) )
		);

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), $this->getMessageAsString( 'smw-admin-statistics-job-docu', Message::PARSE ) )
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
				Html::element( 'div', array( 'class' => 'smw-admin-statistics-job-content' ), $this->getMessageAsString( 'smw-data-lookup' ) )
			)
		);
	}

	private function outputQueryCacheStatistics() {

		$this->outputFormatter->addHTML(
			Html::element( 'h2', array(), $this->getMessageAsString( 'smw-admin-statistics-querycache-title' ) )
		);

		$cachedQueryResultPrefetcher = ApplicationFactory::getInstance()->singleton( 'CachedQueryResultPrefetcher' );

		if ( !$cachedQueryResultPrefetcher->isEnabled() ) {
			return $this->outputFormatter->addHTML(
				Html::rawElement( 'p', array(), $this->getMessageAsString( array( 'smw-admin-statistics-querycache-disabled' ), Message::PARSE ) )
			);
		}

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', array(), $this->getMessageAsString( array( 'smw-admin-statistics-querycache-explain' ), Message::PARSE ) )
		);

		$this->outputFormatter->addHTML(
			'<pre>' . $this->outputFormatter->encodeAsJson( $cachedQueryResultPrefetcher->getStats() ) . '</pre>'
		);
	}

}
