<?php

namespace SMW\Elastic\Admin;

use Html;
use WebRequest;
use SMW\ApplicationFactory;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\Elastic\Indexer\FileIndexer;
use SMW\Utils\HtmlColumns;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\Elastic\Indexer\Replication\CheckReplicationTask;
use SMW\EntityCache;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ReplicationInfoProvider extends InfoProviderHandler {

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var CheckReplicationTask
	 */
	private $checkReplicationTask;

	/**
	 * @since 3.1
	 *
	 * @param OutputFormatter $outputFormatter
	 * @param CheckReplicationTask $checkReplicationTask
	 * @param EntityCache $entityCache
	 */
	public function __construct( OutputFormatter $outputFormatter, CheckReplicationTask $checkReplicationTask, EntityCache $entityCache ) {
		parent::__construct( $outputFormatter );
		$this->checkReplicationTask = $checkReplicationTask;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getSupplementTask() {
		return 'replication';
	}

	/**
	 * @since 3.1
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-elastic-replication-function-title' ),
			[ 'action' => $this->getTask() ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-elastic-replication-intro',
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
			$this->msg( 'smw-admin-supplementary-elastic-replication-header-title' )
		);

		$this->outputFormatter->addParentLink(
			[ 'action' => $this->getParentTask() ],
			'smw-admin-supplementary-elastic-title'
		);

		$this->outputInfo();
	}

	private function outputInfo() {

		$this->outputFormatter->addModules( 'ext.smw.purge' );

		$html = Html::rawElement(
			'div',
			[
				'class' => 'plainlinks'
			],
			$this->msg( 'smw-admin-supplementary-elastic-replication-docu', Message::PARSE )
		);

		$this->outputFormatter->addHTML(
			$html
		);

		$connection = $this->getStore()->getConnection( 'elastic' );
		$failures = $this->checkReplicationTask->getReplicationFailures();

		if ( $failures === false ) {
			return;
		}

		$pages = [];
		$files = [];

		foreach ( $failures as $hash ) {
			$title = DIWikiPage::doUnserialize( $hash )->getTitle();

			if ( $title->getNamespace() === NS_FILE ) {
				$files[] = $this->buildFromFile( $title );
			} else {
				$pages[] = $this->buildFromTitle( $title );
			}
		}

		$this->outputFormatter->addHTML(
			Html::element( 'h2', [], $this->msg( 'smw-admin-supplementary-elastic-replication-pages' ) )
		);

		if ( $pages === [] ) {
			$html = $this->msg( 'smw_result_noresults' );
		} else {
			$html = Html::rawElement( 'ul', [], implode( '', $pages ) );
		}

		$this->outputFormatter->addHTML( $html );

		if ( $files === [] ) {
			return;
		}

		$this->outputFormatter->addHTML(
			Html::element( 'h2', [], $this->msg( 'smw-admin-supplementary-elastic-replication-files' ) ) .
			Html::rawElement(
				'p',
				[
					'class' => 'plainlinks'
				],
				$this->msg( 'smw-admin-supplementary-elastic-replication-files-docu', Message::PARSE )
			)
		);

		$filesCols = new HtmlColumns();
		$filesCols->addContents( $files );

		$this->outputFormatter->addHTML(
			$filesCols->getHtml()
		);
	}

	private function buildFromFile( $title ) {

		$response = '';

		$key = $this->entityCache->makeCacheKey(
			$title,
			FileIndexer::INGEST_RESPONSE
		);

		if ( ( $response = $this->entityCache->fetch( $key ) ) !== false ) {

			if ( is_string( $response ) ) {
				$response = json_decode( $response, true );
			}

			if ( isset( $response['error'] ) ) {
				foreach ( $response['error'] as $key => $value) {
					if ( $key === 'root_cause' ) {
						$response = json_encode( $value );
					}
				}
			} else {
				$response = '';
			}

			if ( $response !== '' ) {
				$response =  $this->error( $response );
			}
		}

		if ( $response === false || $response === '' ) {
			$response = $this->purge( $title );
		}

		return Html::rawElement(
			'a',
			[
				'href' => $title->getFullUrl()
			],
			$title->getPrefixedText()
		) . "&nbsp;($response)";
	}

	private function error( $error ) {
		return Html::rawElement(
			'span',
			[
				'style' => 'color:red;'
			],
			$this->msg( 'smw-error' )
		) . '&nbsp;' . Html::rawElement(
			'span',
			[
				'style' => 'font-size:12px;'
			],
			$error
		);
	}

	private function purge( $title ) {
		return Html::rawElement(
			'a',
			[
				'class' => 'purge',
				'data-title' => $title->getPrefixedText(),
				'href' => $title->getFullUrl( [ 'action' => 'purge' ] )
			],
			$this->msg( 'smw_purge' )
		);
	}

	private function buildFromTitle( $title ) {

		$response = $this->purge( $title );

		return Html::rawElement(
			'li',
			[],
			Html::rawElement(
				'a',
				[
					'href' => $title->getFullUrl()
				],
				$title->getPrefixedText()
			) . "&nbsp;($response)"
		);
	}

}
