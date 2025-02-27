<?php

namespace SMW\MediaWiki\Specials;

use Html;
use SMW\MediaWiki\Specials\PendingTasks\IncompleteSetupTasks;
use SMW\Utils\HtmlTabs;
use SpecialPage;

/**
 * Displays pending tasks in connection with Semantic MediaWiki.
 *
 * @license GPL-2.0-or-later
 *
 * @since 3.2
 * @author mwjames
 */
class SpecialPendingTaskList extends SpecialPage {

	/**
	 * @codeCoverageIgnore
	 */
	public function __construct() {
		parent::__construct( 'PendingTaskList', '', false );
	}

	/**
	 * @see SpecialPage::execute
	 */
	public function execute( $query ) {
		$this->addHelpLink(
			$this->msg( 'smw-helplink', 'Pending_tasks' )->escaped(),
			true
		);

		$output = $this->getOutput();
		$output->addModuleStyles( 'ext.smw.styles' );
		$output->addModuleStyles( 'ext.smw.special.styles' );

		$this->setHeaders();

		$output->addHTML( $this->buildHTML() );

		return true;
	}

	/**
	 * @see SpecialPage::getGroupName
	 */
	protected function getGroupName() {
		return 'smw_group/maintenance';
	}

	private function buildHTML() {
		$isEmpty = true;

		$htmlTabs = new HtmlTabs();

		$pendingTasks = [
			new IncompleteSetupTasks()
		];

		foreach ( $pendingTasks as $pendingTask ) {
			$content = $pendingTask->getHtml();

			if ( $content !== '' && $isEmpty ) {
				$isEmpty = false;
			}

			$htmlTabs->tab(
				'setup',
				$this->msg( $pendingTask->getTitle() )->text(),
				[
					'hide'  => $content === ''
				]
			);

			$htmlTabs->content( 'setup', $content );
		}

		$html = $htmlTabs->buildHTML(
			[ 'class' => 'smw-pendingtasks' ]
		);

		return Html::rawElement(
			'p',
			[],
			$this->msg( ( $isEmpty ? 'smw-pendingtasks-intro-empty' : 'smw-pendingtasks-intro' ) )->parse()
		) . $html;
	}

}
