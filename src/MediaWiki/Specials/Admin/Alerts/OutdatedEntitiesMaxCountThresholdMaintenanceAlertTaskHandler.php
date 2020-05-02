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
class OutdatedEntitiesMaxCountThresholdMaintenanceAlertTaskHandler extends TaskHandler {

	/**
	 * Defines the threshold for a max count of outdated entities that triggers
	 * an alert.
	 */
	const MAXCOUNT_THRESHOLD = 20000;

	/**
	 * @var Store
	 */
	private $store;

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
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$count = $this->fetchCount();

		if ( $count < self::MAXCOUNT_THRESHOLD ) {
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
				'smw_iw' => SMW_SQL3_SMWDELETEIW
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
				$this->msg( "smw-admin-maintenancealerts-outdatedentitiesmaxcount-alert-title" )
			) .	Html::rawElement(
				'p',
				[],
				$this->msg( [ 'smw-admin-maintenancealerts-outdatedentitiesmaxcount-alert', $count, self::MAXCOUNT_THRESHOLD ], Message::PARSE )
			)
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-admin-alerts smw-admin-alerts-outdates-entities'
			],
			$html
		);
	}

}
