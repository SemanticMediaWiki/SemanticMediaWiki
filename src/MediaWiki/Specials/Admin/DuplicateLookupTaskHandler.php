<?php

namespace SMW\MediaWiki\Specials\Admin;

use SMW\Message;
use Html;
use WebRequest;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class DuplicateLookupTaskHandler extends TaskHandler {

	/**
	 * @var OutputFormatter
	 */
	private $outputFormatter;

	/**
	 * @since 3.0
	 *
	 * @param OutputFormatter $outputFormatter
	 */
	public function __construct( OutputFormatter $outputFormatter ) {
		$this->outputFormatter = $outputFormatter;
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( $task ) {
		return $task === 'duplookup';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->getSpecialPageLinkWith(
			$this->getMessageAsString( 'smw-admin-supplementary-duplookup-title' ),
			[
				'action' => 'duplookup'
			]
		);

		return Html::rawElement(
			'li',
			[],
			$this->getMessageAsString(
				[
					'smw-admin-supplementary-duplookup-intro',
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

		$this->outputFormatter->setPageTitle(
			$this->getMessageAsString( 'smw-admin-supplementary-duplookup-title' )
		);

		$this->outputFormatter->addParentLink(
			[
				'tab' => 'supplement'
			]
		);

		$this->outputFormatter->addHelpLink(
			$this->getMessageAsString( 'smw-admin-supplementary-duplookup-helplink' )
		);

		$this->outputFormatter->addHtml(
			Html::rawElement(
				'p',
				[
					'class' => 'plainlinks'
				],
				$this->getMessageAsString( 'smw-admin-supplementary-duplookup-docu', Message::PARSE )
			)
		);

		$this->outputFormatter->addHtml(
			Html::rawElement(
				'h2',
				[],
				$this->getMessageAsString( 'smw_result_results' )
			)
		);

		// Ajax is doing the query and result display to avoid a timeout issue
		$this->outputFormatter->addHtml(
			Html::rawElement(
				'div',
				[
					'class' => 'smw-admin-supplementary-duplookup',
					'data-config' => json_encode(
						[
							'contentClass' => 'smw-admin-supplementary-duplookup-content',
							'errorClass'   => 'smw-admin-supplementary-duplookup-error'
						]
					)
				],
				Html::element(
					'div',
					[
						'class' => 'smw-admin-supplementary-duplookup-error'
					]
				) . Html::rawElement(
					'div',
					[
						'class' => 'smw-admin-supplementary-duplookup-content'
					],
					Html::element(
						'div',
						[
							'style' => 'font-style: italic;margin-bottom:10px;'
						],
						$this->getMessageAsString( 'smw-data-lookup-with-wait' )
					) . Html::element(
						'div',
						[
							'class' => 'smw-loading-image-dots'
						]
					)
				)
			)
		);
	}

}
