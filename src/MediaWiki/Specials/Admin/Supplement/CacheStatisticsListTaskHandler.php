<?php

namespace SMW\MediaWiki\Specials\Admin\Supplement;

use Html;
use SMW\ApplicationFactory;
use SMW\Message;
use WebRequest;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;

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
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === 'stats/cache';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-operational-statistics-cache-title' ),
			[ 'action' => 'stats/cache' ]
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
			$this->msg( 'smw-admin-supplementary-operational-statistics-cache-title' )
		);

		$this->outputFormatter->addParentLink(
			[ 'action' => 'stats' ],
			'smw-admin-supplementary-operational-statistics-title'
		);

		$this->outputQueryCacheStatistics();
	}

	private function outputQueryCacheStatistics() {

		$this->outputFormatter->addHTML(
			Html::element( 'h2', [], $this->msg( 'smw-admin-statistics-querycache-title' ) )
		);

		$cachedQueryResultPrefetcher = ApplicationFactory::getInstance()->singleton( 'CachedQueryResultPrefetcher' );

		if ( !$cachedQueryResultPrefetcher->isEnabled() ) {
			$msg = $this->msg(
				[ 'smw-admin-statistics-querycache-disabled' ],
				Message::PARSE
			);

			return $this->outputFormatter->addHTML(
				Html::rawElement( 'p', [], $msg )
			);
		}

		$msg = $this->msg(
			[ 'smw-admin-statistics-querycache-explain' ],
			Message::PARSE
		);

		$this->outputFormatter->addHTML(
			Html::rawElement( 'p', [], $msg )
		);

		$this->outputFormatter->addAsPreformattedText(
			$this->outputFormatter->encodeAsJson( $cachedQueryResultPrefetcher->getStats() )
		);
	}

}
