<?php

namespace SMW\Elastic\Admin;

use Html;
use SMW\Elastic\Connection\Client as ElasticClient;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class MappingsInfoProvider extends InfoProviderHandler {

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSupplementTask() {
		return 'mappings';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->getMessageAsString( 'smw-admin-supplementary-elastic-mappings-title' ),
			[ 'action' => $this->getTask() ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->getMessageAsString(
				[
					'smw-admin-supplementary-elastic-mappings-intro',
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

		$this->outputFormatter->setPageTitle( 'Elasticsearch mappings' );

		$this->outputFormatter->addParentLink(
			[ 'action' => $this->getParentTask() ],
			'smw-admin-supplementary-elastic-title'
		);

		$this->outputInfo();
	}

	private function outputInfo() {

		$connection = $this->getStore()->getConnection( 'elastic' );

		$mappings = [
			$connection->getMapping(
				[
					'index' => $connection->getIndexNameByType( ElasticClient::TYPE_DATA )
				]
			),
			$connection->getMapping(
				[
					'index' => $connection->getIndexNameByType( ElasticClient::TYPE_LOOKUP )
				]
			)
		];

		$this->outputFormatter->addAsPreformattedText(
			$this->outputFormatter->encodeAsJson( $mappings )
		);
	}

}
