<?php

namespace SMW\MediaWiki\Specials\Admin\Supplement;

use Html;
use SMW\Message;
use WebRequest;
use SMW\MediaWiki\Specials\Admin\TaskHandler;
use SMW\MediaWiki\Specials\Admin\OutputFormatter;
use SMW\MediaWiki\Specials\Admin\ActionableTask;

/**
 * @license GNU GPL v2+
 * @since   3.0
 *
 * @author mwjames
 */
class DuplicateLookupTaskHandler extends TaskHandler implements ActionableTask {

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
	public function getSection() {
		return self::SECTION_SUPPLEMENT;
	}

	/**
	 * @since 3.2
	 *
	 * {@inheritDoc}
	 */
	public function getTask() : string {
		return 'duplicate-lookup';
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function isTaskFor( string $action ) : bool {
		return $action === $this->getTask();
	}

	/**
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function getHtml() {

		$link = $this->outputFormatter->createSpecialPageLink(
			$this->msg( 'smw-admin-supplementary-duplookup-title' ),
			[
				'action' => $this->getTask()
			]
		);

		return Html::rawElement(
			'li',
			[],
			$this->msg(
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
			$this->msg( [ 'smw-admin-main-title', $this->msg( 'smw-admin-supplementary-duplookup-title' ) ] )
		);

		$this->outputFormatter->addParentLink(
			[
				'tab' => 'supplement'
			]
		);

		$this->outputFormatter->addHelpLink(
			$this->msg( 'smw-admin-supplementary-duplookup-helplink' )
		);

		$this->outputFormatter->addHtml(
			Html::rawElement(
				'p',
				[
					'class' => 'plainlinks'
				],
				$this->msg( 'smw-admin-supplementary-duplookup-docu', Message::PARSE )
			)
		);

		$placeholder = Html::rawElement(
			'div',
			[
				'class' => 'smw-admin-supplementary-duplicate-lookup-placeholder',
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

		// Ajax is doing the query and result display to avoid a timeout issue
		$html = Html::rawElement(
				'div',
				[
					'class' => 'smw-admin-supplementary-duplicate-lookup',
					'style' => 'opacity:0.5;position: relative;',
					'data-config' => json_encode(
						[
							'contentClass' => 'smw-admin-supplementary-duplookup-content',
							'errorClass'   => 'smw-admin-supplementary-duplookup-error'
						]
					)
				],
				Html::rawElement(
				'div',
				[
					'class' => 'smw-admin-supplementary-duplookup-error'
				]
			) . Html::rawElement(
				'div',
				[
					'class' => 'smw-jsonview-menu',
				]
			) . Html::rawElement(
				'pre',
				[
					'class' => 'smw-admin-supplementary-duplookup-content'
				],
				$placeholder
			)
		);

		$this->outputFormatter->addHtml( $html );
	}

}
