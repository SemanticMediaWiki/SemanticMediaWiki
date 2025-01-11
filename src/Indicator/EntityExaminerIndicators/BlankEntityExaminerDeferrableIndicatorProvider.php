<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\DIWikiPage;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Localizer\MessageLocalizerTrait;

/**
 * Demonstrates how to create a deferrable examiner to produce a possible violation
 * message for a specific aspect of an entity.
 *
 * Deferrable means that the examiner is only called after a wikipage has been
 * rendered and the check is called from within the `run-entity-examiner` API.
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class BlankEntityExaminerDeferrableIndicatorProvider implements TypableSeverityIndicatorProvider, DeferrableIndicatorProvider {

	use MessageLocalizerTrait;

	/**
	 * @var
	 */
	private $indicators = [];

	/**
	 * @var bool
	 */
	private $isDeferredMode = false;

	/**
	 * @var string
	 */
	private $severityType = TypableSeverityIndicatorProvider::SEVERITY_WARNING;

	/**
	 * @since 3.2
	 *
	 * @param bool $isDeferredMode
	 */
	public function setDeferredMode( bool $isDeferredMode ) {
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
		return 'smw-entity-examiner-deferred-void';
	}

	/**
	 * @since 3.2
	 *
	 * @param DIWikiPage $subject
	 * @param array $options
	 *
	 * @return bool
	 */
	public function hasIndicator( DIWikiPage $subject, array $options ) {
		if ( $this->isDeferredMode ) {
			return $this->runCheck( $subject, $options );
		}

		$this->indicators = [ 'id' => $this->getName() ];

		return $this->indicators !== [];
	}

	/**
	 * @since 3.2
	 *
	 * @return
	 */
	public function getIndicators() {
		return $this->indicators;
	}

	/**
	 * @since 3.2
	 *
	 * @return
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
		return '';
	}

	private function runCheck( $subject, $options ) {
		$options['dir'] = isset( $options['isRTL'] ) && $options['isRTL'] ? 'rtl' : 'ltr';

		// Doing some checks here ...
		$content = '';

		$this->indicators = [
			'id'      => $this->getName(),
			'title'   => 'foo',
			'content' => $content
		];
	}

}
