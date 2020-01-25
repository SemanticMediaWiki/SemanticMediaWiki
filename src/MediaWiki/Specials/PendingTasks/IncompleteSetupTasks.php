<?php

namespace SMW\MediaWiki\Specials\PendingTasks;

use Html;
use SMW\Message;
use SMW\SetupFile;

/**
 * @license GNU GPL v2+
 * @since   3.2
 *
 * @author mwjames
 */
class IncompleteSetupTasks {

	/**
	 * @var SetupFile
	 */
	private $setupFile;

	/**
	 * @since 3.2
	 *
	 * @param SetupFile|null $setupFile
	 */
	public function __construct( SetupFile $setupFile = null ) {
		$this->setupFile = $setupFile;

		if ( $this->setupFile === null ) {
			$this->setupFile = new SetupFile();
		}
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getTitle() : string {
		return 'smw-pendingtasks-tab-setup';
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getHtml() : string {

		$incompleteTasks = $this->setupFile->findIncompleteTasks();
		$count = $this->setupFile->get( SetupFile::PREVIOUS_VERSION ) !== null ? 2 : 1;

		$html = '';

		if ( $incompleteTasks !== [] ) {
			$html = Html::rawElement(
				'p',
				[
					'style' => 'margin-top:10px;color:#888',
					//'class' => 'smw-callout smw-callout-error'
				],
				Message::get( [ 'smw-pendingtasks-setup-intro', $count ], Message::PARSE, Message::USER_LANGUAGE )
			) . $this->buildList( $incompleteTasks );
		}

		return $html;
	}

	private function buildList( array $messages ) {

		$html = '';

		foreach ( $messages as $message ) {
			$html .= Html::rawElement( 'li', [], Message::get( $message, Message::PARSE ) );
		}

		$html = Html::rawElement(
			'fieldset',
			[],
			Html::rawElement(
				'legend',
				[],
				Message::get( "smw-pendingtasks-setup-tasks" )
			) . Html::rawElement(
				'div',
				[
					'class' => 'plainlinks'
				],
				"<ul>$html</ul>"
			)
		);

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-pending-task'
			],
			$html
		);
	}

}
