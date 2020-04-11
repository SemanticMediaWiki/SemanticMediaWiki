<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\DIWikiPage;
use SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\PermissionAware;
use SMW\MediaWiki\Permission\PermissionExaminerAware;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerCompositeIndicatorProvider implements CompositeIndicatorProvider, PermissionExaminerAware {

	/**
	 * @var CompositeIndicatorHtmlBuilder
	 */
	private $compositeIndicatorHtmlBuilder;

	/**
	 * @var []
	 */
	private $indicatorProviders = [];

	/**
	 * @var PermissionExaminer
	 */
	private $permissionExaminer;

	/**
	 * @var []
	 */
	private $indicators = [];

	/**
	 * @var []
	 */
	protected $modules = [ 'smw.entityexaminer' ];

	/**
	 * @since 3.2
	 *
	 * @param CompositeIndicatorHtmlBuilder $compositeIndicatorHtmlBuilder
	 * @param array $indicatorProviders
	 */
	public function __construct( CompositeIndicatorHtmlBuilder $compositeIndicatorHtmlBuilder, array $indicatorProviders ) {
		$this->compositeIndicatorHtmlBuilder = $compositeIndicatorHtmlBuilder;
		$this->indicatorProviders = $indicatorProviders;
	}

	/**
	 * @see PermissionExaminerAware::setPermissionExaminer
	 * @since 3.2
	 *
	 * @param PermissionExaminer $permissionExaminer
	 */
	public function setPermissionExaminer( PermissionExaminer $permissionExaminer ) {
		$this->permissionExaminer = $permissionExaminer;
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
		return $this->modules;
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getName() : string {
		return 'smw-entity-examiner';
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
	 * @since 3.2
	 *
	 * @param DIWikiPage $subject
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function hasIndicator( DIWikiPage $subject, array $options ) {

		if ( isset( $options['action'] ) && ( $options['action'] === 'edit' || $options['action'] === 'history' ) ) {
			return false;
		}

		if ( isset( $options['diff'] ) && $options['diff'] !== null ) {
			return false;
		}

		return $this->checkIndicators( $subject, $options );
	}

	private function checkIndicators( DIWikiPage $subject, array $options ) : bool {

		$indicatorProviders = [];
		$options['dir'] = isset( $options['isRTL'] ) && $options['isRTL'] ? 'rtl' : 'ltr';
		$options['options_raw'] = json_encode( $options );

		foreach ( $this->indicatorProviders as $indicatorProvider ) {

			if ( $indicatorProvider instanceof PermissionExaminerAware ) {
				$indicatorProvider->setPermissionExaminer( $this->permissionExaminer );
			}

			if (
				$indicatorProvider instanceof PermissionAware &&
				!$indicatorProvider->hasPermission( $this->permissionExaminer ) ) {
				continue;
			}

			if ( !$indicatorProvider->hasIndicator( $subject, $options ) ) {
				continue;
			}

			$this->modules = array_merge( $this->modules, $indicatorProvider->getModules() );
			$indicatorProviders[] = $indicatorProvider;
		}

		if ( $indicatorProviders === [] ) {
			return false;
		}

		$options['highlighter_title'] = 'smw-entity-examiner-indicator';
		$options['placeholder_title'] = 'smw-entity-examiner-check';
		$options['subject'] = $subject->getHash();

		$content = $this->compositeIndicatorHtmlBuilder->buildHTML(
			$indicatorProviders,
			$options
		);

		if ( $content !== '' ) {
			$this->indicators[$this->getName()] = $content;
		}

		return $this->indicators !== [];
	}

}
