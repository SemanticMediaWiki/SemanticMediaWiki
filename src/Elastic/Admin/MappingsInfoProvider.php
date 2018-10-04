<?php

namespace SMW\Elastic\Admin;

use Html;
use SMW\Elastic\Connection\Client as ElasticClient;
use WebRequest;
use SMW\Utils\HtmlTabs;

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
			$this->msg( 'smw-admin-supplementary-elastic-mappings-title' ),
			[ 'action' => $this->getTask() ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
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

		$this->outputFormatter->addHtml(
			Html::rawElement( 'p', [], $this->msg( 'smw-admin-supplementary-elastic-mappings-docu' ) )
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'es-mapping' );
		$htmlTabs->setActiveTab( 'summary' );

		$htmlTabs->tab( 'summary', $this->msg( 'smw-admin-supplementary-elastic-mappings-summary' ) );

		$htmlTabs->content(
			'summary',
			'<pre>' . $this->outputFormatter->encodeAsJson( $this->buildSummary( $mappings ) ) . '</pre>'
		);

		$htmlTabs->tab( 'fields', $this->msg( 'smw-admin-supplementary-elastic-mappings-fields' ) );

		$htmlTabs->content(
			'fields',
			'<pre>' . $this->outputFormatter->encodeAsJson( $mappings ) . '</pre>'
		);

		$html = $htmlTabs->buildHTML( [ 'class' => 'es-mapping' ] );

		$this->outputFormatter->addHtml(
			$html
		);

		$this->outputFormatter->addInlineStyle(
			'.es-mapping #tab-summary:checked ~ #tab-content-summary,' .
			'.es-mapping #tab-fields:checked ~ #tab-content-fields {' .
			'display: block;}'
		);
	}

	private function buildSummary( $mappings ) {

		$count = [
			ElasticClient::TYPE_DATA => [
				'fields' => [
					'property_fields' => 0,
					'nested_fields' => 0
				],
				'total' => 0
			],
			ElasticClient::TYPE_LOOKUP => [
				'fields' => [
					'property_fields' => 0,
					'nested_fields' => 0
				],
				'total' => 0
			]
		];

		foreach ( $mappings as $inx ) {
			foreach ( $inx as $key => $value ) {

				if ( isset( $value['mappings'][ElasticClient::TYPE_DATA] ) ) {
					foreach ( $value['mappings'][ElasticClient::TYPE_DATA]['properties'] as $k => $val ) {
						foreach ( $val as $p => $v ) {
							if ( $p === 'properties' ) {
								foreach ( $v as $field => $mappings ) {
									if ( is_string( $field ) ) {
										$count[ElasticClient::TYPE_DATA]['fields']['property_fields']++;
									}

									if ( isset( $mappings['fields'] ) ) {
										$count[ElasticClient::TYPE_DATA]['fields']['nested_fields'] += count( $mappings['fields'] );
									}
								}
							} elseif ( $p === 'type' ) {
								$count[ElasticClient::TYPE_DATA]['fields']['property_fields']++;
							} elseif ( $p === 'fields' ) {
								$count[ElasticClient::TYPE_DATA]['fields']['nested_fields'] += count( $v );
							}
						}
					}

					$count[ElasticClient::TYPE_DATA]['total'] = $count[ElasticClient::TYPE_DATA]['fields']['property_fields'] +
					$count[ElasticClient::TYPE_DATA]['fields']['nested_fields'];
				}

				if ( isset( $value['mappings'][ElasticClient::TYPE_LOOKUP] ) ) {
					foreach ( $value['mappings'][ElasticClient::TYPE_LOOKUP]['properties'] as $k => $val ) {
						foreach ( $val as $p => $v ) {

							if ( $p === 'properties' ) {
								foreach ( $v as $field => $mappings ) {
									if ( is_string( $field ) ) {
										$count[ElasticClient::TYPE_LOOKUP]['fields']['property_fields']++;
									}

									if ( isset( $mappings['fields'] ) ) {
										$count[ElasticClient::TYPE_LOOKUP]['fields']['nested_fields'] += count( $mappings['fields'] );
									}
								}
							} elseif ( $p === 'type' ) {
								$count[ElasticClient::TYPE_LOOKUP]['fields']['property_fields']++;
							}
						}
					}

					$count[ElasticClient::TYPE_LOOKUP]['total'] = $count[ElasticClient::TYPE_LOOKUP]['fields']['property_fields'] +
					$count[ElasticClient::TYPE_LOOKUP]['fields']['nested_fields'];
				}
			}
		}

		return $count;
	}

}
