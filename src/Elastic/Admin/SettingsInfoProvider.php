<?php

namespace SMW\Elastic\Admin;

use SMW\Elastic\Connection\Client as ElasticClient;
use Html;
use WebRequest;

/**
 * @license GNU GPL v2+
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
			$this->getMessageAsString( 'smw-admin-supplementary-elastic-settings-title' ),
			[ 'action' => 'elastic/settings' ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->getMessageAsString(
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
			[ 'action' => 'elastic' ],
			'smw-admin-supplementary-elastic-title'
		);

		$this->outputInfo();
	}

	private function outputInfo() {

		$connection = $this->getStore()->getConnection( 'elastic' );

		$settings = [
			$connection->getSettings(
				[
					'index' => $connection->getIndexNameByType( ElasticClient::TYPE_DATA )
				]
			),
			$connection->getSettings(
				[
					'index' => $connection->getIndexNameByType( ElasticClient::TYPE_LOOKUP )
				]
			)
		];

		$this->outputFormatter->addAsPreformattedText(
			$this->outputFormatter->encodeAsJson( $settings )
		);
	}

}
