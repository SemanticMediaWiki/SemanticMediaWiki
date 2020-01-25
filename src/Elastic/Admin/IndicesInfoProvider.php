<?php

namespace SMW\Elastic\Admin;

use Html;
use SMW\Message;
use WebRequest;
use SMW\Utils\HtmlTabs;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class IndicesInfoProvider extends InfoProviderHandler {

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getSupplementTask() {
		return 'indices';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-elastic-indices-title' ),
			[ 'action' => $this->getTask() ]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
				[
					'smw-admin-supplementary-elastic-indices-intro',
					$link
				]
			)
		);
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function handleRequest( WebRequest $webRequest ) {

		$this->outputFormatter->setPageTitle( 'Elasticsearch indices' );

		$this->outputFormatter->addParentLink(
			[ 'action' => $this->getParentTask() ],
			'smw-admin-supplementary-elastic-title'
		);

		$this->outputInfo();
	}

	private function outputInfo() {

		$connection = $this->getStore()->getConnection( 'elastic' );

		$html = Html::rawElement(
			'p',
			[
				'class' => 'plainlinks'
			],
			$this->msg(
				[
					'smw-admin-supplementary-elastic-statistics-docu',
				],
				Message::PARSE
			)
		);

		$this->outputFormatter->addHtml( $html );

		$indices = $connection->cat( 'indices' );
		ksort( $indices );

		$htmlTabs = new HtmlTabs();
		$htmlTabs->setGroup( 'es-indices' );
		$htmlTabs->setActiveTab( 'indices' );

		$htmlTabs->tab(
			'indices',
			$this->msg( 'smw-admin-supplementary-elastic-indices-title' )
		);

		$htmlTabs->content(
			'indices',
			$this->getJsonView( 'indices' ,$this->outputFormatter->encodeAsJson( $indices ), 3 )
		);

		$htmlTabs->tab(
			'statistics',
			$this->msg( 'smw-admin-supplementary-elastic-statistics-title' )
		);

		$htmlTabs->content(
			'statistics',
			$this->getJsonView( 'statistics', $this->outputFormatter->encodeAsJson( $connection->stats( 'indices' ) ), 2 )
		);

		$html = $htmlTabs->buildHTML( [ 'class' => 'es-indices' ] );

		$this->outputFormatter->addHtml(
			$html
		);

		$this->outputFormatter->addInlineStyle(
			'.es-indices #tab-indices:checked ~ #tab-content-indices,' .
			'.es-indices #tab-statistics:checked ~ #tab-content-statistics {' .
			'display: block;}'
		);
	}

	private function getJsonView( $id, $data, $level = 1 ) {

		$placeholder = Html::rawElement(
			'div',
			[
				'class' => 'smw-schema-placeholder-message',
			],
			$this->msg( 'smw-data-lookup-with-wait' ) .
			"\n\n\n" .$this->msg( 'smw-preparing' ) . "\n"
		) .	Html::rawElement(
			'span',
			[
				'class' => 'smw-overlay-spinner medium',
				'style' => 'transform: translate(-50%, -50%);'
			]
		);

		return Html::rawElement(
				'div',
				[
					'id' => 'smw-admin-configutation-json',
					'class' => '',
				],
				Html::rawElement(
					'div',
					[
						'class' => 'smw-jsonview-menu',
					]
				) . Html::rawElement(
					'pre',
					[
						'id' => "smw-json-container-$id",
						'class' => 'smw-json-container smw-json-placeholder',
						'data-level' => $level
					],
					$placeholder . Html::rawElement(
						'div',
						[
							'class' => 'smw-json-data'
						],
						$data
				)
			)
		);
	}

}
