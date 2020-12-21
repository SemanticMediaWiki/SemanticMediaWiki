<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\Utils\TemplateEngine;
use SMW\Localizer\Message;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class CompositeIndicatorHtmlBuilder {

	use MessageLocalizerTrait;

	/**
	 * @var TemplateEngine
	 */
	private $templateEngine;

	/**
	 * @var string
	 */
	private $languageCode = '';

	/**
	 * @since 3.2
	 *
	 * @param TemplateEngine $templateEngine
	 */
	public function __construct( TemplateEngine $templateEngine ) {
		$this->templateEngine = $templateEngine;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $indicatorProviders
	 * @param array $options
	 *
	 * @return string
	 */
	public function buildHTML( array $indicatorProviders, array $options ) : string {

		if ( !isset( $options['subject'] ) ) {
			throw new RuntimeException( "Expected a subject reference!" );
		}

		$this->languageCode = $options['uselang'] ?? Message::USER_LANGUAGE;

		$this->templateEngine->load( '/indicator/tabpanel.tab.ms', 'tabpanel_tab_template' );
		$this->templateEngine->load( '/indicator/tabpanel.tabset.ms', 'tabpanel_tabset_template' );
		$content = '';
		$tabset = '';

		$options = [
			'highlighter_title' => $options['highlighter_title'],
			'placeholder_title' => $options['placeholder_title'],
			'dir' => $options['dir'],
			'uselang' => $options['uselang'],
			'subject' => $options['subject'],
			'options_raw' => $options['options_raw'],
			'count' => count( $indicatorProviders ),
			'is_placeholder' => true,
			'has_deferred' => false,
			'error_count' => 0,
			'warning_count' => 0
		];

		foreach ( $indicatorProviders as $key => $indicatorProvider ) {
			$indicator = [];
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

			if ( $indicatorProvider instanceof DeferrableIndicatorProvider ) {
				$options['has_deferred'] = $indicatorProvider->isDeferredMode() === false;

				if ( $indicatorProvider->isDeferredMode() ) {
					$options['is_placeholder'] = false;
				}
			}

			if ( $indicatorProvider instanceof CompositeIndicatorProvider ) {
				foreach ( $indicatorProvider->getIndicators() as $k => $value ) {

					if ( $value['content'] === '' ) {
						continue;
					}

					$indicator['tab_id'] = "itab$k";
					$indicator['title'] = $value['title'];
					$indicator['checked'] = $content === '' ? 'checked' : '';
					$indicator['severity_class'] = $value['severity_class'] ?? $severityClass;

					$options['error_count'] += $value['error_count'] ?? 0;
					$options['warning_count'] += $value['warning_count'] ?? 0;

					$content .= $value['content'];
					$tabset .= $this->tab( $indicator );
				}
			} else {
				$options['is_placeholder'] = false;
				$indicator = $indicatorProvider->getIndicators();
				$indicator['tab_id'] = "itab" . ( $indicator['id'] ?? 'unkown' );

				$indicator['subject'] = htmlspecialchars( $options['subject'], ENT_QUOTES );
				$indicator['dir'] = $options['dir'];
				$indicator['checked'] = $content === '' ? 'checked' : '';
				$indicator['severity_class'] = $severityClass;

				$content .= $this->content( $indicator );
				$tabset .= $this->tab( $indicator );
			}
		}

		if ( $options['is_placeholder'] ) {
			$content = $this->placeholder( $options );
		} elseif ( $content !== '' ) {
			$content = $this->highlighter( $content, $tabset, $options );
		}

		return $content;
	}

	private function content( $indicator ) {
		$this->templateEngine->compile( 'tabpanel_tab_template', $indicator );
		return $this->templateEngine->code( 'tabpanel_tab_template' );
	}

	private function tab( $indicator ) {
		$this->templateEngine->compile( 'tabpanel_tabset_template', $indicator );
		return $this->templateEngine->code( 'tabpanel_tabset_template' );
	}

	private function highlighter( $content, $tabset, $options ) {

		$this->templateEngine->load( '/indicator/comment.ms', 'comment_template' );

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( [ 'smw-entity-examiner-indicator-suggestions', $options['count'] ], Message::PARSE, $this->languageCode )
			]
		);

		$top = $this->templateEngine->code( 'comment_template' );
		$bottom = '';

		$this->templateEngine->load( '/indicator/tabpanel.ms', 'tabpanel_template' );
		$this->templateEngine->compile( 'tabpanel_template', [ 'content' => $content, 'tabset' => $tabset ] );
		$content = $this->templateEngine->code( 'tabpanel_template' );

		$this->templateEngine->load( '/indicator/composite.highlighter.ms', 'highlighter_template' );

		$this->templateEngine->compile(
			'highlighter_template',
			[
				'title' => $this->msg( [ $options['highlighter_title'], $options['count'] ], Message::PARSE, $this->languageCode ),
				'content' => htmlspecialchars( $content, ENT_QUOTES ),
				'top' => htmlspecialchars( $top, ENT_QUOTES ),
				'bottom' => htmlspecialchars( $bottom, ENT_QUOTES ),
				'has_deferred' => $options['has_deferred'] ? 'yes' : 'no',
				'subject' => htmlspecialchars( $options['subject'], ENT_QUOTES ),
				'dir' => $options['dir'],
				'uselang' => $options['uselang'],
				'count' => $options['count'],
				'options' => $options['options_raw'],

				// If there is at least one error issue then classify the `issue panel`
				// as error as well.
				'severity' => $options['error_count'] > 0 ? 'error' : 'warning'
			]
		);

		return $this->templateEngine->code( 'highlighter_template' );
	}

	private function placeholder( $options ) {

		$this->templateEngine->load( '/indicator/composite.placeholder.ms', 'placeholder_template' );

		$this->templateEngine->compile(
			'placeholder_template',
			[
				'title' => $this->msg( [ $options['placeholder_title'], $options['count'] ], Message::PARSE, $this->languageCode ),
				'subject' => htmlspecialchars( $options['subject'], ENT_QUOTES ),
				'dir' => $options['dir'],
				'uselang' => $options['uselang']
			]
		);

		return $this->templateEngine->code( 'placeholder_template' );
	}

}
