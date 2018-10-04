<?php

namespace SMW\Elastic\Admin;

use Html;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\Message;
use SMW\ApplicationFactory;
use WebRequest;
use SMW\Elastic\Indexer\ReplicationStatus;
use SMW\Elastic\Connection\Client as ElasticClient;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticClientTaskHandler extends TaskHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @var array
	 */
	private $taskHandlers = [];

	/**
	 * @since 3.0
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param array $taskHandlers
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
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {

		// Root
		$actions = [
			'elastic'
		];

		foreach ( $this->taskHandlers as $taskHandler ) {
			$actions[] = $taskHandler->getTask();
		}

		return in_array( $task, $actions );
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$connection = $this->getStore()->getConnection( 'elastic' );

		if ( !$connection->ping() ) {
			return '';
		}

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-elastic-title' ),
			[ 'action' => 'elastic' ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-elastic-intro',
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

		$connection = $this->getStore()->getConnection( 'elastic' );
		$action = $webRequest->getText( 'action' );

		if ( !$connection->ping() ) {
			return $this->outputNoNodesAvailable();
		} elseif ( $action === 'elastic' ) {
			$this->outputHead();
		} else {
			foreach ( $this->taskHandlers as $taskHandler ) {
				if ( $taskHandler->isTaskFor( $action ) ) {

					$taskHandler->setStore(
						$this->getStore()
					);

					return $taskHandler->handleRequest( $webRequest );
				}
			}
		}

		$this->outputInfo();
	}

	private function outputNoNodesAvailable() {

		$this->outputHead();

		$html = Html::element(
			'div',
			[ 'class' => 'smw-callout smw-callout-error' ],
			'Elasticsearch has no active nodes available.'
		);

		$this->outputFormatter->addHTML( $html );
	}

	private function outputHead() {

		$this->outputFormatter->setPageTitle( 'Elasticsearch' );
		$this->outputFormatter->addHelpLink( 'https://www.semantic-mediawiki.org/wiki/Help:ElasticStore' );

		$this->outputFormatter->addParentLink(
			[ 'tab' => 'supplement' ]
		);

		$html = Html::rawElement(
			'p',
			[ 'class' => 'plainlinks' ],
			$this->msg( [ 'smw-admin-supplementary-elastic-docu' ], Message::PARSE )
		);

		$this->outputFormatter->addHTML( $html );
	}

	private function outputInfo() {

		$connection = $this->getStore()->getConnection( 'elastic' );
		$html = '';

		$this->outputFormatter->addAsPreformattedText(
			$this->outputFormatter->encodeAsJson( $connection->info() )
		);

		$replicationStatus = new ReplicationStatus(
			$connection
		);

		$jobQueue = ApplicationFactory::getInstance()->getJobQueue();

		$html .= Html::element(
			'li',
			[],
			$this->msg( [ 'smw-admin-supplementary-elastic-status-last-active-replication', $replicationStatus->get( 'last_update' ) ] )
		);

		$html .= Html::rawElement(
			'li', [ 'class' => 'plainlinks' ],
			$this->msg( [ 'smw-admin-supplementary-elastic-status-recovery-job-count', $jobQueue->getQueueSize( 'smw.elasticIndexerRecovery') ], Message::PARSE )
		);

		if ( $connection->getConfig()->dotGet( 'indexer.experimental.file.ingest', false ) ) {
			$html .= Html::rawElement(
				'li',
				[ 'class' => 'plainlinks' ],
				$this->msg( [ 'smw-admin-supplementary-elastic-status-file-ingest-job-count', $jobQueue->getQueueSize( 'smw.elasticFileIngest') ], Message::PARSE )
			);
		}

		if ( $connection->hasLock( ElasticClient::TYPE_DATA ) ) {
			$html .= Html::rawElement(
				'li',
				[ 'class' => 'plainlinks' ],
				$this->msg( [ 'smw-admin-supplementary-elastic-status-rebuild-lock', '✓' ], Message::TEXT )
			);
		}

		$html .= Html::element(
			'li',
			[],
			$this->msg( [ 'smw-admin-supplementary-elastic-status-refresh-interval', $replicationStatus->get( 'refresh_interval' ) ] )
		);

		$this->outputFormatter->addHTML(
			Html::element( 'h3', [], $this->msg(
				'smw-admin-supplementary-elastic-status-replication' )
			) . Html::rawElement( 'ul', [], $html )
		);

		$list = '';

		foreach ( $this->taskHandlers as $taskHandler ) {
			$list .= $taskHandler->getHtml();
		}

		$this->outputFormatter->addHTML(
			Html::element( 'h3', [], $this->msg( 'smw-admin-supplementary-elastic-functions' ) )
		);

		$this->outputFormatter->addHTML(
			Html::rawElement( 'ul', [], $list )
		);
	}

}
