<?php

namespace SMW\MediaWiki\Specials\Admin\Alerts;

use Html;
use SMW\Store;
use SMW\Message;
use SMW\SQLStore\SQLStore;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;

/**
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class ByNamespaceInvalidEntitiesMaintenanceAlertTaskHandler extends TaskHandler {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var array
	 */
	private $namespacesWithSemanticLinks = [];

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $namespacesWithSemanticLinks
	 */
	public function setNamespacesWithSemanticLinks( array $namespacesWithSemanticLinks ) {
		$this->namespacesWithSemanticLinks = $namespacesWithSemanticLinks;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$count = $this->fetchCount();

		if ( $count == 0 ) {
			return '';
		}

		return $this->buildHTML( $count );
	}

	private function fetchCount() {

		$connection = $this->store->getConnection( 'mw.db' );

		$row = $connection->selectRow(
			SQLStore::ID_TABLE,
			'COUNT(smw_id) AS count',
			[
				'smw_namespace NOT IN (' . $connection->makeList( array_keys( $this->namespacesWithSemanticLinks ) ) . ')'
			],
			__METHOD__
		);

		return $row !== false ? (int)$row->count : 0;
	}

	private function buildHTML( $count ) {

		$html = Html::rawElement(
			'fieldset',
			[
				'class' => "smw-admin-alerts-section-legend"
			],
			Html::rawElement(
				'legend',
				[
					'class' => "smw-admin-alerts-section-legend"
				],
				$this->msg( "smw-admin-maintenancealerts-invalidentities-alert-title" )
			) .	Html::rawElement(
				'p',
				[],
				$this->msg( [ 'smw-admin-maintenancealerts-invalidentities-alert', $count ], Message::PARSE )
			)
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-admin-alerts smw-admin-alerts-invalid-entities'
			],
			$html
		);
	}

}
