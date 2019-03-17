<?php

namespace SMW\MediaWiki\Content;

use Html;
use SMW\Utils\HtmlTabs;
use SMW\Message;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HtmlBuilder {

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 * @param array $params
	 *
	 * @return string
	 */
	public function build( $key, array $params ) {
		return $this->{$key}( $params );
	}

	private function schema_head( $params ) {

		$list = [];
		$text = '';
		$type_description = '';

		if ( $params['link'] !== '' ) {
			$list[] = Html::rawElement(
				'span',
				[
					'class' => 'plainlinks'
				],
				$params['link']
			);
		}

		if ( isset( $params['type_description'] ) ) {
			$type_description .= Html::rawElement(
				'p',
				[
					'class' => 'smw-schema-type-description plainlinks'

				],
				$params['type_description']
			);
		}

		if ( $params['description'] !== '' ) {
			$type_description .= Html::rawElement(
				'p',
				[
					'class' => 'smw-schema-description plainlinks'
				],
				$params['description']
			);
		}

		$htmlTabs = new HtmlTabs();

		$htmlTabs->setActiveTab(
			$params['error'] !== '' ? 'schema-error' : 'schema-summary'
		);

		$htmlTabs->tab( 'schema-summary', $params['schema-title'] );
		$htmlTabs->tab(
			'schema-error',
			$params['error-title'],
			[
				'hide' => $params['error'] === '', 'class' => 'error-label'
			]
		);

		$htmlTabs->content( 'schema-summary', $text );
		$htmlTabs->content( 'schema-error', $params['error'] );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-schema' ]
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'schema-head'
			],
			$type_description . $html
		);
	}

	private function schema_body( $params ) {

		$class = '';
		$placeholder = '';

		if ( $params['unknown_type'] !== false ) {
			$class = ' unknown-type';
		}

		if ( $params['isYaml'] === false ) {
			$placeholder = Html::rawElement(
				'div',
				[
					'class' => 'smw-schema-placeholder-message',
				],
				Message::get( 'smw-data-lookup-with-wait' ) .
				"\n\n\n" . Message::get( 'smw-preparing' ) . "\n"
			) .	Html::rawElement(
				'span',
				[
					'class' => 'smw-overlay-spinner medium',
					'style' => 'transform: translate(-50%, -50%);'
				]
			);
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'schema-body' . $class,
			],
			Html::rawElement(
				'div',
				[
					'id' => 'smw-schema',
					'class' => 'smw-schema-placeholder',
				],  Html::rawElement(
				'pre',
				[
					'id' => 'smw-schema-container'
				],
				$placeholder . Html::rawElement(
					'div',
					[
						'class' => 'smw-schema-data' . ( $params['isYaml'] ? '-yaml' : '' ),
					],
					$params['text']
				)
			)
			)
		);
	}

	private function schema_error_text( $params ) {

		$html = Html::rawElement(
				'ul',
				[
					'class' => 'smw-schema-validation-error-list'
				],
				'<li>' . implode( '</li><li>', $params['list'] ) . '</li>'
		);

		$html = Html::rawElement(
			'div',
			[
				'class' => 'smw-schema-validation-error'
			],
			$params['schema']
		) . $html;

		return $html;
	}

	private function schema_error( $params ) {

		$html = Html::rawElement(
			'span',
			[
				'class' => 'schema-error'
			],
			$params['text']
		);

		return $html . '&nbsp;' . Html::rawElement(
			'span',
			[],
			":&nbsp;" . $params['msg']
		);
	}

	private function schema_footer( $params ) {

		$html = Html::rawElement(
			'div',
			[
				'class' => 'schema-tags'
			],
			Html::rawElement(
				'div',
				[],
				Html::rawElement(
					'a',
					[
						'href' => $params['href_type']
					],
					$params['msg_type']
				) . ':&nbsp;' . $params['link_type']
			)
		);

		if ( $params['tags'] !== [] ) {
			$html .= Html::rawElement(
				'div',
				[
					'class' => 'schema-tags'
				],
				Html::rawElement(
					'div',
					[],
					Html::rawElement(
						'a',
						[
							'href' => $params['href_tag']
						],
						$params['msg_tag']
					) . ':' . '<ul><li>' . implode( '</li><li>',  $params['tags'] ) . '</li></ul>'
				)
			);
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'schema-footer'
			],
			$html
		);
	}

	private function schema_unknown_type( $params ) {
		return Html::rawElement(
			'p',
			[
				'class' => 'smw-callout smw-callout-error plainlinks'
			],
			$params['msg']
		);
	}

	private function schema_help_link( $params ) {
		return Html::rawElement(
			'a',
			[
				'href' => $params['href'],
				'target' => '_blank',
				'class' => 'mw-helplink',
			]
		);
	}

}
