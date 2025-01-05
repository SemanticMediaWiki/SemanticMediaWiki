<?php

namespace SMW\Elastic\Admin;

use Html;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Utils\JsonView;
use WebRequest;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SettingsInfoProvider extends InfoProviderHandler {

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSupplementTask() {
		return 'settings';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {
		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-elastic-settings-title' ),
			[ 'action' => $this->getTask() ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-elastic-settings-intro',
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
		$this->outputFormatter->setPageTitle( 'Elasticsearch settings' );

		$this->outputFormatter->addParentLink(
			[ 'action' => $this->getParentTask() ],
			'smw-admin-supplementary-elastic-title'
		);

		$this->outputInfo();
	}

	private function outputInfo() {
		$connection = $this->getStore()->getConnection( 'elastic' );

		$settings = [
			ElasticClient::TYPE_DATA => $connection->getSettings(
				[
					'index' => $connection->getIndexName( ElasticClient::TYPE_DATA )
				]
			),
			ElasticClient::TYPE_LOOKUP => $connection->getSettings(
				[
					'index' => $connection->getIndexName( ElasticClient::TYPE_LOOKUP )
				]
			)
		];

		$html = ( new JsonView() )->create(
			'elastic-settings',
			$this->outputFormatter->encodeAsJson( $settings ),
			2
		);

		$this->outputFormatter->addHtml(
			$html
		);
	}

}
