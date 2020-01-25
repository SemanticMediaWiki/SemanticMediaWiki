<?php

namespace SMW\Elastic\Admin;

use Html;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\ActionableTask;
use SMW\Message;
use SMW\ApplicationFactory;
use WebRequest;
use SMW\Elastic\Indexer\ReplicationStatus;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Elastic\Config;
use SMW\Utils\HtmlTabs;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ElasticClientTaskHandler extends TaskHandler implements ActionableTask {

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
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getTask() : string {
		return 'elastic';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( string $action ) : bool {

		// Root
		$actions = [
			$this->getTask()
		];

		foreach ( $this->taskHandlers as $taskHandler ) {

			if ( !$taskHandler instanceof ActionableTask ) {
				continue;
			}

			$actions[] = $taskHandler->getTask();
		}

		return in_array( $action, $actions );
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-elastic-title' ),
			[ 'action' => $this->getTask() ]
		);

		$html = Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-elastic-intro',
					$link
				]
			)
		);

		$html = Html::rawElement(
			'h3',
			[],
			$this->msg( 'smw-admin-supplementary-elastic-section-subtitle' )
		) . Html::rawElement(
			'ul',
			[],
			$html
		);

		return $html;
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
			return $this->outputNoNodesAvailable( $connection );
		} elseif ( $action === $this->getTask() ) {
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

	private function outputNoNodesAvailable( $connection ) {

		$this->outputHead();
		$config = $connection->getConfig();

		$html = Html::rawElement(
			'h3',
			[ 'class' => 'smw-title' ],
			$this->msg( [ 'smw-admin-supplementary-elastic-replication-header-title' ] )
		) . Html::rawElement(
			'p',
			[],
			$this->msg( [ 'smw-admin-supplementary-elastic-no-connection' ], Message::PARSE )
		). Html::rawElement(
			'h4',
			[ 'class' => 'smw-title' ],
			$this->msg( [ 'smw-admin-supplementary-elastic-endpoints' ] )
		) . Html::rawElement(
			'pre',
			[],
			json_encode( $config->safeGet( Config::ELASTIC_ENDPOINTS, [] ), JSON_PRETTY_PRINT )
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

		$applicationFactory = ApplicationFactory::getInstance();
		$elasticFactory = $applicationFactory->singleton( 'ElasticFactory' );

		$replicationStatus = $elasticFactory->newReplicationStatus(
			$connection
		);

		$jobQueue = $applicationFactory->getJobQueue();

		$html .= Html::element(
			'li',
			[],
			$this->msg( [ 'smw-admin-supplementary-elastic-status-last-active-replication', $replicationStatus->get( 'last_update' ) ] )
		);

		$html .= Html::element(
			'li',
			[],
			$this->msg( [ 'smw-admin-supplementary-elastic-status-replication-monitoring', $connection->getConfig()->dotGet( 'indexer.monitor.entity.replication' ) ? '✓' : '✗' ] )
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

		$list = '';

		foreach ( $this->taskHandlers as $taskHandler ) {

			if ( !$taskHandler instanceof ActionableTask ) {
				continue;
			}

			$list .= $taskHandler->getHtml();
		}

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'elastic' );

		$htmlTabs->tab( 'status', $this->msg( 'smw-admin-supplementary-elastic-status-replication' ) );
		$htmlTabs->content( 'status', Html::rawElement( 'ul', [ 'style' => 'margin-top:10px;' ], $html ) );

		$htmlTabs->tab( 'tasks', $this->msg( 'smw-admin-supplementary-elastic-functions' ) );
		$htmlTabs->content( 'tasks', Html::rawElement( 'ul', [ 'style' => 'margin-top:10px;' ], $list ) );

		$html = $htmlTabs->buildHTML( [ 'class' => 'elastic' ] );

		$this->outputFormatter->addInlineStyle(
			'.elastic #tab-status:checked ~ #tab-content-status,' .
			'.elastic #tab-tasks:checked ~ #tab-content-tasks {' .
			'display: block;}'
		);

		$this->outputFormatter->addHTML(
			$html
		);
	}

}
