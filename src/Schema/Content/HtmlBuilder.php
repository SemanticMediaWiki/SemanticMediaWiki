<?php

namespace SMW\Schema\Content;

use Html;
use SMW\Utils\HtmlTabs;

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

	private function schema_head_2( $params ) {

		$html = Html::rawElement(
			'p',
			[
				'style' => 'padding-top:0px;padding-bottom:0px;',
				'class' => 'plainlinks'

			],
			$params['type_description']
		);

		if ( $params['error'] !== '' ) {
			$html .= Html::rawElement( 'h3', [], $params['error-title'] ) . Html::rawElement(
				'p',
				[
					'style' => 'border: 0px solid #eaecf0;padding:0px;'

				],
				$params['error']
			);
		}

		$html .= Html::rawElement( 'hr', [ 'style' => 'margin-top: 20px;margin-bottom: 20px;background-color: #ddd;' ], '' );

		return Html::rawElement(
			'div',
			[
				'class' => 'schema-head'
			],
			$html
		);
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

		if ( $params['description'] !== '' ) {
			$list[] = Html::rawElement(
				'span',
				[
					'class' => 'plainlinks'
				],
				$params['description']
			);
		}

		if ( isset( $params['type_description'] ) ) {
			$type_description .= Html::rawElement(
				'p',
				[
					'style' => 'padding-bottom:5px;padding-bottom:0px;',
					'class' => 'plainlinks'

				],
				$params['type_description']
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

		$error = Html::rawElement(
			'p',
			[
				'style' => 'border: 0px solid #eaecf0;padding:0px;'

			],
			$params['error']
		);

		$htmlTabs->content( 'schema-error', $error );

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

		$class = $params['unknown_type'] !== false ? ' unknown-type' : '';

		return Html::rawElement(
			'div',
			[
				'class' => 'schema-body' . $class
			],
			$params['text']
		);
	}

	private function schema_error_text( $params ) {

		$html = Html::rawElement(
				'ul',
				[
					'style' => 'padding-left:0px;'
				],
				'<li>' . implode( '</li><li>', $params['list'] ) . '</li>'
		);

		$html = Html::rawElement(
			'div',
			[
				'style' => 'padding-left:0px;padding-top:1px;'
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
