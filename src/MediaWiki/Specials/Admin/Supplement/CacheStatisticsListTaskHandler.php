<?php

namespace SMW\MediaWiki\Specials\Admin\Supplement;

use Html;
use SMW\ApplicationFactory;
use SMW\Message;
use WebRequest;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\Utils\HtmlTabs;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class CacheStatisticsListTaskHandler extends TaskHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @since 3.0
	 *
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( OutputFormatter $outputFormatter ) {
		$this->outputFormatter = $outputFormatter;
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
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getTask() {
		return 'stats/cache';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === $this->getTask();
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-operational-statistics-cache-title' ),
			[ 'action' => $this->getTask() ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-operational-statistics-cache-intro',
					$link
				]
			)
		);
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		$this->outputFormatter->setPageTitle(
			$this->msg( [ 'smw-admin-main-title', $this->msg( 'smw-admin-supplementary-operational-statistics-cache-title' ) ] )
		);

		$this->outputFormatter->addParentLink(
			[ 'action' => 'stats' ],
			'smw-admin-supplementary-operational-statistics-title'
		);

		$this->outputQueryCacheStatistics();
	}

	private function outputQueryCacheStatistics() {

		$resultCache = ApplicationFactory::getInstance()->singleton( 'ResultCache' );

		if ( !$resultCache->isEnabled() ) {
			$msg = $this->msg(
				[ 'smw-admin-statistics-querycache-disabled' ],
				Message::PARSE
			);

			return $this->outputFormatter->addHTML(
				Html::rawElement( 'p', [], $msg )
			);
		}

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', [], $this->msg( 'smw-admin-statistics-section-explain' ) )
		);

		$placeholder = Html::rawElement(
			'div',
			[
				'class' => 'smw-json-placeholder-message',
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
					'id' => 'smw-admin-querycache-json',
					'class' => 'smw-json-placeholder',
				],  Html::rawElement(
				'pre',
				[
					'id' => 'smw-json-container'
				],
				$placeholder . Html::rawElement(
					'div',
					[
						'class' => 'smw-json-data'
					],
					$this->outputFormatter->encodeAsJson( $resultCache->getStats() )
				)
			)
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'cache-statistics' );
		$htmlTabs->setActiveTab( 'report' );

		$htmlTabs->tab( 'report', $this->msg( 'smw-admin-statistics-querycache-title' ) );
		$htmlTabs->content( 'report', $html );

		$htmlTabs->tab( 'legend', $this->msg( 'smw-legend' ) );
		$htmlTabs->content( 'legend', Html::rawElement(
					'p', [] , $this->msg( 'smw-admin-statistics-querycache-legend', Message::PARSE ) ) );

		$html = $htmlTabs->buildHTML( [ 'class' => 'cache-statistics' ] );

		$this->outputFormatter->addHtml( $html );

		$this->outputFormatter->addInlineStyle(
			'.cache-statistics #tab-report:checked ~ #tab-content-report,' .
			'.cache-statistics #tab-legend:checked ~ #tab-content-legend {' .
			'display: block;}'
		);
	}

}
