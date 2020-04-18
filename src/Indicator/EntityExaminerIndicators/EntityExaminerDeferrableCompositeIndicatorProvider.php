<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\Localizer\MessageLocalizerTrait;
use SMW\Localizer\Message;
use SMW\DIWikiPage;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider;
use SMW\Utils\TemplateEngine;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\Permission\PermissionExaminerAware;
use SMW\MediaWiki\Permission\PermissionAware;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityExaminerDeferrableCompositeIndicatorProvider implements DeferrableIndicatorProvider, CompositeIndicatorProvider, PermissionExaminerAware {

	use MessageLocalizerTrait;

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
	private $modules = [ 'smw.entityexaminer' ];

	/**
	 * @var boolean
	 */
	private $isDeferredMode = false;

	/**
	 * @var string
	 */
	private $languageCode = '';

	/**
	 * @since 3.2
	 *
	 * @param array $indicatorProviders
	 */
	public function __construct( array $indicatorProviders ) {
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
	 * @param boolean $isDeferredMode
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
	 * @return string
	 */
	public function getName() : string {
		return 'deferrablecompoundintegrityexaminer';
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
		return $this->checkIndicators( $subject, $options ) !== [];
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
	public function getInlineStyle() {
		return '';
	}

	private function checkIndicators( $subject, $options ) {

		$indicatorProviders = [];
		$options['dir'] = isset( $options['isRTL'] ) && $options['isRTL'] ? 'rtl' : 'ltr';
		$options['error_count'] = 0;
		$options['warning_count'] = 0;

		$this->languageCode = $options['uselang'] ?? Message::USER_LANGUAGE;

		foreach ( $this->indicatorProviders as $indicatorProvider ) {

			if ( $indicatorProvider instanceof PermissionExaminerAware ) {
				$indicatorProvider->setPermissionExaminer( $this->permissionExaminer );
			}

			if (
				$indicatorProvider instanceof PermissionAware &&
				!$indicatorProvider->hasPermission( $this->permissionExaminer ) ) {
				continue;
			}

			if ( !$indicatorProvider instanceof DeferrableIndicatorProvider ) {
				continue;
			}

			$indicatorProvider->setDeferredMode( $this->isDeferredMode );

			if ( !$indicatorProvider->hasIndicator( $subject, $options ) ) {
				continue;
			}

			$this->modules = array_merge( $this->modules, $indicatorProvider->getModules() );
			$indicatorProviders[$indicatorProvider->getName()] = $indicatorProvider;
		}

		if ( $indicatorProviders !== [] ) {
			$this->buildHTML( $subject, $indicatorProviders, $options );
		}

		return $this->indicators;
	}

	private function buildHTML( $subject, array $indicatorProviders, array $options ) {

		$this->templateEngine = new TemplateEngine();

		$this->templateEngine->bulkLoad(
			[
				'/indicator/tabpanel.tab.ms' => 'tabpanel_tab_template',
				'/indicator/text.ms' => 'text_template'
			]
		);

		$count = count( $indicatorProviders );

		foreach ( $indicatorProviders as $key => $indicatorProvider ) {

			$severityClass = '';

			if ( $indicatorProvider instanceof TypableSeverityIndicatorProvider ) {
				if ( $indicatorProvider->isSeverityType( TypableSeverityIndicatorProvider::SEVERITY_ERROR ) ) {
					$options['error_count']++;
					$severityClass = 'smw-indicator-severity-error';
				} elseif ( $indicatorProvider->isSeverityType( TypableSeverityIndicatorProvider::SEVERITY_WARNING ) ) {
					$options['warning_count']++;
					$severityClass = 'smw-indicator-severity-warning';
				}
			}

			$args = [
				'tab_id' => $key,
				'dir' => $options['dir'],
				'subject' => $subject->getHash(),
				'checked' => '',
				'background' => '',
				'color' => '',
				'count' => $count
			];

			$content = $this->getContent( $indicatorProvider, $args );

			if ( $content === '' ) {
				$count--;
			}

			$this->indicators[$key] = [
				'title' => $this->msg( $indicatorProvider->getName(), Message::TEXT, $this->languageCode ),
				'severity_class' => $severityClass,
				'error_count' => $options['error_count'],
				'warning_count' => $options['warning_count'],
				'content' => $content
			];
		}
	}

	private function getContent( $indicatorProvider, array $args ) {

		if ( $this->isDeferredMode ) {
			$args += $indicatorProvider->getIndicators();
			$args['checked'] = $args['count'] == 1 ? 'checked' : '';
		} else {
			$args['title'] = $this->msg( $indicatorProvider->getName(), Message::TEXT, $this->languageCode );

			$this->templateEngine->compile( 'text_template',
				[
					'text' => $this->msg( [ 'smw-entity-examiner-deferred-check-awaiting-response', $args['title'] ], Message::TEXT, $this->languageCode )
				]
			);

			$args['content'] = $this->templateEngine->publish( 'text_template' );
		}

		if ( $args['content'] === '' ) {
			return '';
		}

		$this->templateEngine->compile( 'tabpanel_tab_template', $args );

		return $this->templateEngine->publish( 'tabpanel_tab_template' );
	}

}
