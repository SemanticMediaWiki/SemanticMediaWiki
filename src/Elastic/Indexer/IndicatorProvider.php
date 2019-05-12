<?php

namespace SMW\Elastic\Indexer;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Store;
use SMW\Message;
use SMW\MediaWiki\IndicatorProvider as IIndicatorProvider;
use SMW\Elastic\Indexer\Replication\CheckReplicationTask;
use SMW\EntityCache;
use Html;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class IndicatorProvider implements IIndicatorProvider {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var []
	 */
	private $indicators = [];

	/**
	 * @var boolean
	 */
	private $checkReplication = false;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param EntityCache $entityCache
	 */
	public function __construct( Store $store, EntityCache $entityCache ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $checkReplication
	 */
	public function canCheckReplication( $checkReplication ) {
		$this->checkReplication = (bool)$checkReplication;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function hasIndicator( Title $title, $options ) {

		if ( $this->checkReplication && $title->exists() ) {
			$this->checkReplication( $title, $options );
		}

		return $this->indicators !== [];
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getIndicators() {
		return $this->indicators;
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getModules() {
		return [ 'smw.check.replication' ];
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getInlineStyle() {
		// The standard helplink interferes with the alignment (due to a text
		// component) therefore disabled it when indicators are present
		return '#mw-indicator-mw-helplink {display:none;}';
	}

	private function checkReplication( $title, $options ) {

		if ( $options['action'] === 'edit' || $options['diff'] !== null || $options['action'] === 'history' ) {
			return;
		}

		$subject = DIWikiPage::newFromTitle(
			$title
		);

		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$property = DIProperty::newFromUserLabel( $subject->getDBKey() );

			if ( !$property->isUserDefined() ) {
				$subject = new DIWikiPage( $property->getKey(), SMW_NS_PROPERTY );
			}
		}

		if ( $this->wasChecked( $subject ) ) {
			return;
		}

		$dir = 'ltr';

		if ( isset( $options['isRTL'] ) && $options['isRTL'] ) {
			$dir = 'rtl';
		}

		$this->indicators['smw-es-replication'] = Html::rawElement(
			'div',
			[
				'class' => 'smw-es-replication smw-icon-indicator-placeholder',
				'title' => Message::get( 'smw-es-replication-check' ),
				'data-subject' => $subject->getHash(),
				'data-dir' => $dir,
			]
		);
	}

	private function wasChecked( $subject ) {

		$connection = $this->store->getConnection( 'elastic' );

		$checkReplicationTask = $this->entityCache->fetch(
			CheckReplicationTask::makeCacheKey( $subject )
		);

		return $connection->ping() === true &&
			$connection->hasMaintenanceLock() === false &&
			$checkReplicationTask === CheckReplicationTask::TYPE_SUCCESS;
	}

}
