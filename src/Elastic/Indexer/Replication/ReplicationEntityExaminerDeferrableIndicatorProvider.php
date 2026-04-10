<?php

namespace SMW\Elastic\Indexer\Replication;

use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\Store;
use SMW\Utils\TemplateEngine;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ReplicationEntityExaminerDeferrableIndicatorProvider implements TypableSeverityIndicatorProvider, DeferrableIndicatorProvider {

	use MessageLocalizerTrait;

	/**
	 * @var
	 */
	private array $indicators = [];

	private bool $checkReplication = false;

	private bool $isDeferredMode = false;

	private string $severityType = '';

	private TemplateEngine $templateEngine;

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly Store $store,
		private readonly EntityCache $entityCache,
		private readonly ReplicationCheck $replicationCheck,
	) {
	}

	/**
	 * @since 3.2
	 *
	 * @param bool $checkReplication
	 */
	public function canCheckReplication( $checkReplication ): void {
		$this->checkReplication = (bool)$checkReplication;
	}

	/**
	 * @since 3.2
	 *
	 * @param bool $isDeferredMode
	 */
	public function setDeferredMode( bool $isDeferredMode ): void {
		$this->isDeferredMode = $isDeferredMode;
	}

	/**
	 * @since 3.2
	 *
	 * @return bool
	 */
	public function isDeferredMode(): bool {
		return $this->isDeferredMode;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $severityType
	 *
	 * @return bool
	 */
	public function isSeverityType( string $severityType ): bool {
		return $this->severityType === $severityType;
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getName(): string {
		return 'smw-entity-examiner-deferred-elastic-replication';
	}

	/**
	 * @since 3.2
	 *
	 * @param WikiPage $subject
	 * @param array $options
	 *
	 * @return bool
	 */
	public function hasIndicator( WikiPage $subject, array $options ): bool {
		if ( $this->checkReplication ) {
			$this->checkReplication( $subject, $options );
		}

		return $this->indicators !== [];
	}

	/**
	 * @since 3.2
	 *
	 * @return
	 */
	public function getIndicators(): array {
		return $this->indicators;
	}

	/**
	 * @since 3.2
	 *
	 * @return
	 */
	public function getModules(): array {
		return [];
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getInlineStyle(): string {
		// The standard helplink interferes with the alignment (due to a text
		// component) therefore disabled it when indicators are present
		return '#mw-indicator-mw-helplink {display:none;}';
	}

	/**
	 * @param WikiPage $subject
	 * @param array $options
	 *
	 * @return void
	 */
	private function checkReplication( WikiPage $subject, array $options ): void {
		$options['dir'] = isset( $options['isRTL'] ) && $options['isRTL'] ? 'rtl' : 'ltr';

		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$property = Property::newFromUserLabel( $subject->getDBKey() );

			if ( !$property->isUserDefined() ) {
				$subject = new WikiPage( $property->getKey(), SMW_NS_PROPERTY );
			}
		}

		if ( $this->wasChecked( $subject ) ) {
			return;
		}

		if ( $this->isDeferredMode ) {
			$this->runCheck( $subject, $options );
			return;
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
	private function runCheck( WikiPage $subject, array $options ): void {
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

	private function wasChecked( WikiPage $subject ): bool {
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
