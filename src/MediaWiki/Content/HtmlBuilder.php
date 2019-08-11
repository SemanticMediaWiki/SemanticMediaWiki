<?php

namespace SMW\MediaWiki\Content;

use Html;
use SMW\Utils\HtmlTabs;
use SMW\Utils\HtmlDivTable;
use SMW\Utils\Html\SummaryTable;
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

		$usage_count = Html::rawElement(
			'span',
			[
				'class' => 'item-count'
			],
			$params['usage_count']
		);

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'schema' );

		$htmlTabs->setActiveTab(
			'schema-summary'
		);

		$htmlTabs->tab( 'schema-summary', $params['summary-title'] );
		$htmlTabs->tab( 'schema-content', $params['schema-title'] );

		$htmlTabs->tab(
			'schema-usage',
			$params['usage-title'] . $usage_count,
			[
				'hide' => $params['usage'] === '', 'class' => 'usage-label'
			]
		);

		$htmlTabs->content( 'schema-summary', $params['schema_summary'] );
		$htmlTabs->content( 'schema-content', $params['schema_body'] );
		$htmlTabs->content( 'schema-usage', $params['usage'] );

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-schema' ]
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'schema-head'
			],
			$type_description
		) . Html::rawElement(
			'div',
			[
				'class' => 'schema-body'
			],
			$html
		);
	}

	private function schema_summary( $params ) {

		$html = '';
		$rows = '';
		$parameters = [];

		foreach ( $params['attributes'] as $key => $value ) {

			if ( $value === '' || $value === null ) {
				continue;
			}

			if ( $key === 'type' ) {
				$key = Html::rawElement(
					'a',
					[
						'href' => $params['attributes_extra']['href_type']
					],
					$params['attributes_extra']['msg_type']
				);

				$parameters[$key] = $params['attributes_extra']['link_type'];
			}

			if ( $key === 'schema_description' ) {
				$key = Html::rawElement(
					'a',
					[
						'href' => $params['attributes_extra']['href_description']
					],
					$params['attributes_extra']['msg_description']
				);

				$parameters[$key] = $value;
			}

			if ( $key === 'type_description' ) {
				$key = $params['attributes_extra']['type_description'];
				$parameters[$key] = $value;
			}

			if ( $key === 'tag' ) {
				$key = Html::rawElement(
					'a',
					[
						'href' => $params['attributes_extra']['href_tag']
					],
					$params['attributes_extra']['msg_tag']
				);

				$parameters[$key] = implode( ', ', $params['attributes_extra']['tags'] );
			}
		}

		$summaryTable = new SummaryTable(
			$parameters
		);

		$html = $summaryTable->buildHTML();


		if ( isset( $params['error_params'] ) && $params['error_params'] !== [] ) {
			$parameters = [];
			$attributes = [];

			$parameters[$params['validator-schema-title']] = $params['validator_schema'];

			foreach ( $params['error_params'] as $prop => $message ) {
				$parameters[$prop] = $message;
				$attributes[$prop] = [ 'style' => 'background-color: #fee7e6;' ];
			}

			$summaryTable = new SummaryTable(
				$parameters
			);

			$summaryTable->setAttributes(
				$attributes
			);

			$html .= Html::rawElement(
				'h3',
				[ 'class' => 'smw-title' ],
				$params['error-title']
			);

			$html .= $summaryTable->buildHTML();
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'schema-summary'
			],
			$html
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
				Message::get( 'smw-data-lookup-with-wait', Message::TEXT, Message::USER_LANGUAGE ) .
				"\n\n\n" . Message::get( 'smw-preparing', Message::TEXT, Message::USER_LANGUAGE ) . "\n"
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
					'id' => 'smw-schema-container',
					'data-level' => 2
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
		return Html::rawElement(
				'ul',
				[
					'class' => 'smw-schema-validation-error-list'
				],
				'<li>' . implode( '</li><li>', $params['list'] ) . '</li>'
		);
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
