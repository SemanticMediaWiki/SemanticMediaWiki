<?php

namespace SMW\Elastic\Admin;

use Html;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class NodesInfoProvider extends InfoProviderHandler {

	/**
	 * @since 3.0
	 *
	 * @return string
	 */
	public function getSupplementTask() {
		return 'nodes';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-elastic-nodes-title' ),
			[ 'action' => $this->getTask() ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-elastic-nodes-intro',
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

		$this->outputFormatter->setPageTitle( 'Elasticsearch nodes' );

		$this->outputFormatter->addParentLink(
			[ 'action' => $this->getParentTask() ],
			'smw-admin-supplementary-elastic-title'
		);

		$this->outputInfo();
	}

	private function outputInfo() {

		$connection = $this->getStore()->getConnection( 'elastic' );

		$nodes = $connection->stats( 'nodes' );
		ksort( $nodes );

		$this->outputFormatter->addAsPreformattedText(
			$this->outputFormatter->encodeAsJson( $nodes )
		);
	}

}
