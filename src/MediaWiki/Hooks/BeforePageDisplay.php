<?php

namespace SMW\MediaWiki\Hooks;

use OutputPage;
use Skin;
use SpecialPage;
use Title;
use SMW\Message;
use Html;

/**
 * BeforePageDisplay hook which allows last minute changes to the
 * output page, e.g. adding of CSS or JavaScript
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class BeforePageDisplay extends HookHandler {

	/**
	 * @since 1.9
	 *
	 * @param OutputPage $outputPage,
	 * @param Skin $skin
	 *
	 * @return boolean
	 */
	public function process( OutputPage $outputPage, Skin $skin ) {

		$title = $outputPage->getTitle();
		$user = $outputPage->getUser();

		// MW 1.26 / T107399 / Async RL causes style delay
		$outputPage->addModuleStyles(
			[
				'ext.smw.style',
				'ext.smw.tooltip.styles'
			]
		);

		if ( $title->getNamespace() === NS_SPECIAL ) {
			$outputPage->addModuleStyles(
				[
					'ext.smw.special.styles'
				]
			);
		}

		// #2726
		if ( $user->getOption( 'smw-prefs-general-options-suggester-textinput' ) ) {
			$outputPage->addModules( 'ext.smw.suggester.textInput' );
		}

		if ( ( $tasks = $this->getOption( 'incomplete_tasks', [] ) ) !== [] ) {
			$outputPage->prependHTML( $this->incompleteTasksHTML( $tasks ) );
		}

		// Add export link to the head
		if ( $title instanceof Title && !$title->isSpecialPage() ) {
			$link['rel']   = 'alternate';
			$link['type']  = 'application/rdf+xml';
			$link['title'] = $title->getPrefixedText();
			$link['href']  = SpecialPage::getTitleFor( 'ExportRDF', $title->getPrefixedText() )->getLocalUrl( 'xmlmime=rdf' );
			$outputPage->addLink( $link );
		}

		$request = $skin->getContext()->getRequest();

		if ( in_array( $request->getVal( 'action' ), [ 'delete', 'edit', 'protect', 'unprotect', 'diff', 'history' ] ) || $request->getVal( 'diff' ) ) {
			return true;
		}

		return true;
	}

	private function incompleteTasksHTML( array $messages ) {

		$html = '';

		foreach ( $messages as $message ) {
			$html .= Html::rawElement( 'li', [], Message::get( $message, Message::PARSE ) );
		}

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-callout smw-callout-error plainlinks'
			],
			Message::get( 'smw-install-incomplete-intro' ) . "<ul>$html</ul>"
		);
	}

}
