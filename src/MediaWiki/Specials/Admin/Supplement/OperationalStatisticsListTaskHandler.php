<?php

namespace SMW\MediaWiki\Specials\Admin\Supplement;

use Html;
use SMW\Message;
use WebRequest;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;

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
	 * @var TaskHandler[]
	 */
	private $taskHandlers = [];

	/**
	 * @since 2.5
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param TaskHandler[] $taskHandlers
	 */
	public function __construct( OutputFormatter $outputFormatter, array $taskHandlers = [] ) {
		$this->outputFormatter = $outputFormatter;
		$this->taskHandlers = $taskHandlers;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_SUPPLEMENT;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return true;
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {

		$actions = [
			'stats',
			'stats/cache'
		];

		return in_array( $task, $actions );
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->getSpecialPageLinkWith(
			$this->msg( 'smw-admin-supplementary-operational-statistics-title' ),
			[ 'action' => 'stats' ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-operational-statistics-intro',
					$link
				]
			)
		);
	}

	/**
	 * @since 2.5
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		$action = $webRequest->getText( 'action' );

		if ( $action === 'stats' ) {
			$this->outputHead();
		} else {
			foreach ( $this->taskHandlers as $taskHandler ) {
				if ( $taskHandler->isTaskFor( $action ) ) {
					$taskHandler->setStore( $this->getStore());
					return $taskHandler->handleRequest( $webRequest );
				}
			}
		}

		$this->outputSemanticStatistics();
		$this->outputJobStatistics();
		$this->outputInfo();
	}

	private function outputHead() {

		$this->outputFormatter->setPageTitle(
			$this->msg( 'smw-admin-supplementary-operational-statistics-title' )
		);

		$this->outputFormatter->addParentLink(
			[ 'tab' => 'supplement' ]
		);
	}

	private function outputInfo() {

		$list = '';

		foreach ( $this->taskHandlers as $taskHandler ) {
			$list .= $taskHandler->getHtml();
		}

		$this->outputFormatter->addHTML(
			Html::element( 'h2', [], $this->msg( 'smw-admin-other-functions' ) )
		);

		$this->outputFormatter->addHTML(
			Html::rawElement( 'ul', [], $list )
		);
	}

	private function outputSemanticStatistics() {

		$semanticStatistics = $this->getStore()->getStatistics();

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', [], $this->msg( [ 'smw-admin-operational-statistics' ], Message::PARSE ) )
		);

		$this->outputFormatter->addHTML(
			Html::element( 'h2', [], $this->msg( 'smw-statistics' ) )
		);

		$this->outputFormatter->addAsPreformattedText(
			$this->outputFormatter->encodeAsJson(
				[
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
				]
			)
		);
	}

	private function outputJobStatistics() {

		$this->outputFormatter->addHTML(
			Html::element( 'h2', [], $this->msg( 'smw-admin-statistics-job-title' ) )
		);

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', [], $this->msg( 'smw-admin-statistics-job-docu', Message::PARSE ) )
		);

		$this->outputFormatter->addHTML(
			Html::rawElement(
				'div',
				[
					'class' => 'smw-admin-statistics-job',
					'data-config' => json_encode( [
						'contentClass' => 'smw-admin-statistics-job-content',
						'errorClass'   => 'smw-admin-statistics-job-error'
					] ),
				],
				Html::element( 'div', [ 'class' => 'smw-admin-statistics-job-error' ], '' ) .
				Html::element( 'div', [ 'class' => 'smw-admin-statistics-job-content' ], $this->msg( 'smw-data-lookup' ) )
			)
		);
	}

}
