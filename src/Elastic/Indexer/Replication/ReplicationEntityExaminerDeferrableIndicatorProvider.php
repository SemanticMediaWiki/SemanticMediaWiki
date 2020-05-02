<?php

namespace SMW\Elastic\Indexer\Replication;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Store;
use SMW\Message;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\EntityCache;
use SMW\Utils\TemplateEngine;
use SMW\Localizer\MessageLocalizerTrait;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ReplicationEntityExaminerDeferrableIndicatorProvider implements TypableSeverityIndicatorProvider, DeferrableIndicatorProvider {

	use MessageLocalizerTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var ReplicationCheck
	 */
	private $replicationCheck;

	/**
	 * @var []
	 */
	private $indicators = [];

	/**
	 * @var boolean
	 */
	private $checkReplication = false;

	/**
	 * @var boolean
	 */
	private $isDeferredMode = false;

	/**
	 * @var string
	 */
	private $severityType = '';

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 * @param EntityCache $entityCache
	 * @param ReplicationCheck $replicationCheck
	 */
	public function __construct( Store $store, EntityCache $entityCache, ReplicationCheck $replicationCheck ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
		$this->replicationCheck = $replicationCheck;
	}

	/**
	 * @since 3.2
	 *
	 * @param boolean $checkReplication
	 */
	public function canCheckReplication( $checkReplication ) {
		$this->checkReplication = (bool)$checkReplication;
	}

	/**
	 * @since 3.2
	 *
	 * @param boolean $type
	 */
	public function setDeferredMode( bool $isDeferredMode ) {
		$this->isDeferredMode = $isDeferredMode;
	}

	/**
	 * @since 3.2
	 *
	 * @return boolean
	 */
	public function isDeferredMode() : bool {
		return $this->isDeferredMode;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $severityType
	 *
	 * @return boolean
	 */
	public function isSeverityType( string $severityType ) : bool {
		return $this->severityType === $severityType;
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getName() : string {
		return 'smw-entity-examiner-deferred-elastic-replication';
	}

	/**
	 * @since 3.2
	 *
	 * @param DIWikiPage $subject
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function hasIndicator( DIWikiPage $subject, array $options ) {

		if ( $this->checkReplication ) {
			$this->checkReplication( $subject, $options );
		}

		return $this->indicators !== [];
	}

	/**
	 * @since 3.2
	 *
	 * @return []
	 */
	public function getIndicators() {
		return $this->indicators;
	}

	/**
	 * @since 3.2
	 *
	 * @return []
	 */
	public function getModules() {
		return [];
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getInlineStyle() {
		// The standard helplink interferes with the alignment (due to a text
		// component) therefore disabled it when indicators are present
		return '#mw-indicator-mw-helplink {display:none;}';
	}

	/**
	 * @param DIWikiPage $subject
	 * @param array $options
	 *
	 * @return void
	 */
	private function checkReplication( $subject, $options ) {

		$options['dir'] = isset( $options['isRTL'] ) && $options['isRTL'] ? 'rtl' : 'ltr';

		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$property = DIProperty::newFromUserLabel( $subject->getDBKey() );

			if ( !$property->isUserDefined() ) {
				$subject = new DIWikiPage( $property->getKey(), SMW_NS_PROPERTY );
			}
		}

		if ( $this->wasChecked( $subject ) ) {
			return;
		}

		if ( $this->isDeferredMode ) {
			return $this->runCheck( $subject, $options );
		}

		$this->indicators = [
			'id' => $this->getName(),
		];
	}

	/**
	 * @param $subject
	 * @param $options
	 *
	 * @return null
	 */
	private function runCheck( $subject, $options ) {

		$html = $this->replicationCheck->checkReplication( $subject, $options );

		$this->templateEngine = new TemplateEngine();
		$this->templateEngine->load( '/indicator/dot.label.ms', 'dot_label_template' );
		$this->templateEngine->load( '/indicator/bottom.marker.ms', 'bottom_marker' );

		if ( $this->replicationCheck->getSeverityType() === ReplicationCheck::SEVERITY_TYPE_ERROR ) {
			$this->severityType = TypableSeverityIndicatorProvider::SEVERITY_ERROR;
		} else {
			$this->severityType = TypableSeverityIndicatorProvider::SEVERITY_WARNING;
		}

		$this->templateEngine->compile(
			'bottom_marker',
			[
				'margin' => isset( $options['dir'] ) && $options['dir'] === 'rtl' ? 'right' : 'left',
				'label' => 'elastic',
				'background-color' => '#cc317c',
				'color' => '#ffffff'
			]
		);

		$this->indicators = [
			'id'      => $this->getName(),
			'content' => $html . ( $html !== '' ? $this->templateEngine->publish( 'bottom_marker' ) : '' )
		];
	}

	private function wasChecked( $subject ) {

		$connection = $this->store->getConnection( 'elastic' );
		$wasChecked = false;

		if (
			$connection->ping() === false ||
			$connection->hasMaintenanceLock() === true ) {
			return false;
		}

		$wasChecked = $this->entityCache->fetch(
			ReplicationCheck::makeCacheKey( $subject )
		);

		return $wasChecked === ReplicationCheck::TYPE_SUCCESS;
	}

}
