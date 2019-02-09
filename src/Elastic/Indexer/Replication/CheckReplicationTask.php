<?php

namespace SMW\Elastic\Indexer\Replication;

use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\Message;
use Html;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CheckReplicationTask extends Task {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var ReplicationStatus
	 */
	private $replicationStatus;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param ReplicationStatus $replicationStatus
	 */
	public function __construct( Store $store, ReplicationStatus $replicationStatus ) {
		$this->store = $store;
		$this->replicationStatus = $replicationStatus;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ) {

		if ( $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = DIWikiPage::doUnserialize(
			$parameters['subject']
		);

		$html = $this->checkReplication(
			$subject,
			$parameters
		);

		return [ 'done' => true, 'html' => $html ];
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 * @param array $options
	 *
	 * @return string
	 */
	public function checkReplication( DIWikiPage $subject, array $options = [] ) {

		$isRTL = isset( $options['dir'] ) && $options['dir'] === 'rtl';
		$html = '';

		$id = $this->store->getObjectIds()->getID( $subject );
		$title = $subject->getTitle();

		// Find the time from elastic
		$dataItem = $this->replicationStatus->getModificationDate(
			$id
		);

		// What is stored in the DB
		$pv = $this->store->getPropertyValues(
			$subject,
			new DIProperty( '_MDAT' )
		);

		if ( $dataItem === false || $pv === [] ) {
			$html = $this->buildHTML( $title->getPrefixedText(), $id, $isRTL );
		} elseif ( !end( $pv )->equals( $dataItem ) ) {
			$dates = [
				'time_es' => $dataItem->getSerialization(),
				'time_store' => end( $pv )->getSerialization()
			];
			$html = $this->buildHTML( $title->getPrefixedText(), $id, $isRTL, $dates );
		}

		return $html;
	}

	private function buildHTML( $title_text, $id, $isRTL = false, $dates = [] ) {

		if ( $isRTL ) {
			$line = '<div style="border-top: 1px solid #ebebeb;margin-top: 10px;margin-bottom: 8px;margin-right: -10px;width: 280px;"></div>';
		} else {
			$line = '<div style="border-top: 1px solid #ebebeb;margin-top: 10px;margin-bottom: 8px;margin-left: -10px;width: 280px;"></div>';
		}

		$content = '';
		$bottom = '<span class="smw-issue-label" style="background-color: #cc317c;color: #ffffff;">elastic</span>';

		if ( $dates !== [] ) {
			$content .= $this->msg( [ 'smw-es-replication-error-divergent-date', $title_text, $id ] );
			$content .= $line;
			$content .= $this->msg( [ 'smw-es-replication-error-divergent-date-detail', $dates['time_es'], $dates['time_store'] ], Message::PARSE );
			$content .= $line;
			$content .= '<span style="font-size:12px;">' . $this->msg( 'smw-es-replication-error-suggestions' ) . '</span>';
		} else {
			$content .= $this->msg( [ 'smw-es-replication-error-missing-id', $title_text, $id ] );
			$content .= $line;
			$content .= '<span style="font-size:12px;">' . $this->msg( 'smw-es-replication-error-suggestions' ) . '</span>';
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-highlighter smw-icon-indicator-replication-error',
				'data-maxWidth' => '280',
				'data-state' => 'inline',
				'data-placement' => 'auto',
				'data-animation' => 'fade',
				'data-theme' => 'wide-popup',
				'data-title' => $this->msg( 'smw-es-replication-error' ),
				'data-content' => $content,
				'data-bottom' => $bottom
			]
		);
	}

	private function msg( $key, $type = Message::TEXT, $lang = Message::USER_LANGUAGE ) {
		return Message::get( $key, $type, $lang );
	}

}
