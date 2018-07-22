<?php

namespace SMW\Elastic\Admin;

use Html;
use SMW\Message;
use WebRequest;

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

		$this->outputFormatter->addHtml( '<h2>Indices</h2>' );

		$indices = $connection->cat( 'indices' );
		ksort( $indices );

		$this->outputFormatter->addAsPreformattedText(
			$this->outputFormatter->encodeAsJson( $indices )
		);

		$this->outputFormatter->addHtml( '<h2>Statistics</h2>' );

		$this->outputFormatter->addAsPreformattedText(
			$this->outputFormatter->encodeAsJson( $connection->stats( 'indices' ) )
		);
	}

}
