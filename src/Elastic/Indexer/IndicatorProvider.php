<?php

namespace SMW\Elastic\Indexer;

use SMWDITime as DITime;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Store;
use SMW\SemanticData;
use SMW\MediaWiki\IndicatorProvider as IIndicatorProvider;
use Title;
use Html;
use SMW\Message;

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
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
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

		if ( $this->checkReplication ) {
			return $this->checkReplication( $title, $options );
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

	private function checkReplication( $title, $options ) {

		if ( $options['action'] === 'edit' || $options['diff'] !== null || $options['action'] === 'history' ) {
			return false;
		}

		$subject = DIWikiPage::newFromTitle(
			$title
		);

		$dir = 'ltr';

		if ( isset( $options['isRTL'] ) && $options['isRTL'] ) {
			$dir = 'rtl';
		}

		$this->indicators['smw-es-replication'] = Html::rawElement(
			'div',
			[
				'class' => 'smw-es-replication',
				'data-subject' => $subject->getHash(),
				'data-dir' => $dir,
			]
		);

		return true;
	}

}
