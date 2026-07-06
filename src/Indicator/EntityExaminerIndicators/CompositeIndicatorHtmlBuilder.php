<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use MediaWiki\Html\TemplateParser;
use RuntimeException;
use SMW\Indicator\IndicatorProviders\CompositeIndicatorProvider;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Localizer\Message;
use SMW\Localizer\MessageLocalizerTrait;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class CompositeIndicatorHtmlBuilder {

	use MessageLocalizerTrait;

	/**
	 * @var string
	 */
	private $languageCode = '';

	/**
	 * @since 3.2
	 */
	public function __construct( private TemplateParser $templateParser ) {
	}

	/**
	 * @since 3.2
	 *
	 * @param array $indicatorProviders
	 * @param array $options
	 *
	 * @return string
	 */
	public function buildHTML( array $indicatorProviders, array $options ): string {
		if ( !isset( $options['subject'] ) ) {
			throw new RuntimeException( "Expected a subject reference!" );
		}

		$this->languageCode = $options['uselang'] ?? Message::USER_LANGUAGE;

		$panels = [];
		$tabsetEntries = [];

		$options = [
			'highlighter_title' => $options['highlighter_title'],
			'placeholder_title' => $options['placeholder_title'],
			'dir' => $options['dir'],
			'uselang' => $options['uselang'] ?? '',
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
				$options['has_deferred'] = !( $indicatorProvider->isDeferredMode() );

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
					$indicator['checked'] = $panels === [];
					$indicator['severity_class'] = $value['severity_class'] ?? $severityClass;
					$indicator['content'] = $value['content'];

					$options['error_count'] += $value['error_count'] ?? 0;
					$options['warning_count'] += $value['warning_count'] ?? 0;

					$panels[] = $this->content( $indicator );
					$tabsetEntries[] = $this->tab( $indicator );
				}
			} else {
				$options['is_placeholder'] = false;
				$indicator = $indicatorProvider->getIndicators();
				$indicator['tab_id'] = "itab" . ( $indicator['id'] ?? 'unkown' );

				$indicator['subject'] = $options['subject'];
				$indicator['dir'] = $options['dir'];
				$indicator['checked'] = $panels === [];
				$indicator['severity_class'] = $severityClass;

				$panels[] = $this->content( $indicator );
				$tabsetEntries[] = $this->tab( $indicator );
			}
		}

		if ( $options['is_placeholder'] ) {
			$content = $this->placeholder( $options );
		} elseif ( $panels !== [] ) {
			$content = $this->highlighter( $panels, $tabsetEntries, $options );
		} else {
			$content = '';
		}

		return $content;
	}

	/**
	 * Builds a `Tab` partial view-model.
	 */
	private function content( array $indicator ): array {
		return [
			'data-tab-id' => $indicator['tab_id'],
			'html-content' => $indicator['content'] ?? ''
		];
	}

	/**
	 * Builds a `Tabset` partial view-model.
	 */
	private function tab( array $indicator ): array {
		return [
			'data-tab-id' => $indicator['tab_id'],
			'is-checked' => (bool)$indicator['checked'],
			'data-severity-class' => $indicator['severity_class'],
			'title' => $indicator['title'] ?? ''
		];
	}

	private function highlighter( array $panels, array $tabsetEntries, array $options ): string {
		$top = $this->templateParser->processTemplate(
			'Comment',
			[
				'html-comment' => $this->msg( [ 'smw-entity-examiner-indicator-suggestions', $options['count'] ], Message::PARSE, $this->languageCode )
			]
		);

		$bottom = '';

		$content = $this->templateParser->processTemplate(
			'Tabpanel',
			[
				'array-tabset' => $tabsetEntries,
				'array-panels' => $panels
			]
		);

		return $this->templateParser->processTemplate(
			'CompositeHighlighter',
			[
				'data-title' => $this->msg( [ $options['highlighter_title'], $options['count'] ], Message::PARSE, $this->languageCode ),
				'data-content' => $content,
				'data-top' => $top,
				'data-bottom' => $bottom,
				'has_deferred' => $options['has_deferred'] ? 'yes' : 'no',
				'subject' => $options['subject'],
				'dir' => $options['dir'],
				'uselang' => $options['uselang'],
				'count' => $options['count'],
				'data-options' => $options['options_raw'],

				// If there is at least one error issue then classify the `issue panel`
				// as error as well.
				'severity' => $options['error_count'] > 0 ? 'error' : 'warning'
			]
		);
	}

	private function placeholder( array $options ): string {
		return $this->templateParser->processTemplate(
			'CompositePlaceholder',
			[
				'title' => $this->msg( [ $options['placeholder_title'], $options['count'] ], Message::PARSE, $this->languageCode ),
				'subject' => $options['subject'],
				'dir' => $options['dir'],
				'uselang' => $options['uselang']
			]
		);
	}

}
