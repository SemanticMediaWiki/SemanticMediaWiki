<?php

namespace SMW\MediaWiki\Specials\Admin\Supplement;

use Html;
use SMW\ApplicationFactory;
use SMW\Message;
use SMW\EntityCache;
use WebRequest;
use SMW\Utils\HtmlTabs;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\MediaWiki\Api\Tasks\TableStatisticsTask;

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
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @since 3.1
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param EntityCache $entityCache
	 */
	public function __construct( OutputFormatter $outputFormatter, EntityCache $entityCache ) {
		$this->outputFormatter = $outputFormatter;
		$this->entityCache = $entityCache;
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

		$legend = Html::rawElement(
			'p',
			[] ,
			$this->msg( 'smw-admin-supplementary-operational-table-statistics-legend', Message::PARSE )
		) . Html::rawElement(
			'p',
			[] ,
			$this->msg( 'smw-admin-supplementary-operational-table-statistics-legend-general', Message::PARSE )
		) . Html::rawElement(
			'h4',
			[] ,
			'smw_object_ids'
		) . Html::rawElement(
			'p',
			[] ,
			$this->msg( 'smw-admin-supplementary-operational-table-statistics-legend-id-table', Message::PARSE )
		) . Html::rawElement(
			'h4',
			[] ,
			'smw_di_blob'
		) . Html::rawElement(
			'p',
			[] ,
			$this->msg( 'smw-admin-supplementary-operational-table-statistics-legend-blob-table', Message::PARSE )
		);

		// Is the result fetchable from cache? If yes, change the tab class.
		$isFromCache = $this->entityCache->contains(
			Task::makeCacheKey( TableStatisticsTask::CACHE_KEY )
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'table-statistics' );
		$htmlTabs->setActiveTab( 'report' );

		$htmlTabs->tab( 'report', $this->msg( 'smw-admin-statistics' ), [ 'class' => $isFromCache ? ' cached' : '' ] );
		$htmlTabs->content( 'report', $html );

		$htmlTabs->tab( 'legend', $this->msg( 'smw-legend' ) );
		$htmlTabs->content( 'legend', $legend );

		$html = $htmlTabs->buildHTML( [ 'class' => 'table-statistics' ] );

		$this->outputFormatter->addHtml( $html );

		$this->outputFormatter->addInlineStyle(
			'.table-statistics #tab-report:checked ~ #tab-content-report,' .
			'.table-statistics #tab-legend:checked ~ #tab-content-legend {' .
			'display: block;}'
		);
	}

}
