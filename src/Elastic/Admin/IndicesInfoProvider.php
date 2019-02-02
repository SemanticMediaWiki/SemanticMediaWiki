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
			'<pre>' . $this->outputFormatter->encodeAsJson( $indices ) . '</pre>'
		);

		$htmlTabs->tab(
			'statistics',
			$this->msg( 'smw-admin-supplementary-elastic-statistics-title' )
		);

		$htmlTabs->content(
			'statistics',
			'<pre>' . $this->outputFormatter->encodeAsJson( $connection->stats( 'indices' ) ) . '</pre>'
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

}
