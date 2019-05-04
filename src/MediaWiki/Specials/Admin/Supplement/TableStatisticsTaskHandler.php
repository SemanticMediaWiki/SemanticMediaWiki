<?php

namespace SMW\MediaWiki\Specials\Admin\Supplement;

use Html;
use SMW\ApplicationFactory;
use SMW\Message;
use WebRequest;
use SMW\Utils\HtmlTabs;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TableStatisticsTaskHandler extends TaskHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @since 3.1
	 *
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( OutputFormatter $outputFormatter ) {
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getSection() {
		return self::SECTION_SUPPLEMENT;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function hasAction() {
		return true;
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getTask() {
		return 'stats/table';
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === $this->getTask();
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-operational-table-statistics-short-title' ),
			[ 'action' => $this->getTask() ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-operational-table-statistics-intro',
					$link
				]
			)
		);
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		$this->outputFormatter->setPageTitle(
			$this->msg( [ 'smw-admin-main-title', $this->msg( 'smw-admin-supplementary-operational-table-statistics-title' ) ] )
		);

		$this->outputFormatter->addParentLink(
			[ 'action' => 'stats' ],
			'smw-admin-supplementary-operational-statistics-title'
		);

		$this->outputStatistics();
	}

	private function outputStatistics() {

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', [], $this->msg( 'smw-admin-supplementary-operational-table-statistics-explain' ) )
		);

		$placeholder = Html::rawElement(
			'div',
			[
				'class' => 'smw-admin-supplementary-table-statistics-content',
			],
			Message::get( 'smw-data-lookup-with-wait' ) .
			"\n\n\n" . Message::get( 'smw-preparing' ) . "\n"
		) .	Html::rawElement(
			'span',
			[
				'class' => 'smw-overlay-spinner medium',
				'style' => 'transform: translate(-50%, -50%);'
			]
		);

		$html = Html::rawElement(
				'div',
				[
					'id' => 'smw-admin-supplementary-table-statistics',
					'style' => 'opacity:0.5;position: relative;',
					'data-config' => json_encode(
						[
							'contentClass' => 'smw-admin-supplementary-table-statistics-content',
							'errorClass'   => 'smw-admin-supplementary-table-statistics-error'
						]
					)
				],
				Html::element(
					'div',
					[
						'class' => 'smw-admin-supplementary-table-statistics-error'
					]
				) . Html::rawElement(
				'pre',
				[
					'class' => 'smw-admin-supplementary-table-statistics-content'
				],
				$this->msg( 'smw-data-lookup-with-wait' ) .
				"\n\n\n" . $this->msg( 'smw-processing' ) . "\n" .
				Html::rawElement(
					'span',
					[
						'class' => 'smw-overlay-spinner medium',
						'style' => 'transform: translate(-50%, -50%);'
					]
				)
			)
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'table-statistics' );
		$htmlTabs->setActiveTab( 'report' );

		$htmlTabs->tab( 'report', $this->msg( 'smw-admin-statistics' ) );
		$htmlTabs->content( 'report', $html );

		$htmlTabs->tab( 'legend', $this->msg( 'smw-legend' ) );
		$htmlTabs->content( 'legend', Html::rawElement(
					'p', [] , $this->msg( 'smw-admin-supplementary-operational-table-statistics-legend', Message::PARSE ) ) );

		$html = $htmlTabs->buildHTML( [ 'class' => 'table-statistics' ] );

		$this->outputFormatter->addHtml( $html );

		$this->outputFormatter->addInlineStyle(
			'.table-statistics #tab-report:checked ~ #tab-content-report,' .
			'.table-statistics #tab-legend:checked ~ #tab-content-legend {' .
			'display: block;}'
		);
	}

}
