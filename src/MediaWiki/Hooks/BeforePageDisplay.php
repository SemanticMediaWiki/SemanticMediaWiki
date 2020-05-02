<?php

namespace SMW\MediaWiki\Hooks;

use OutputPage;
use Skin;
use SpecialPage;
use Title;
use SMW\Message;
use Html;
use SMW\MediaWiki\HookListener;
use SMW\OptionsAwareTrait;

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
class BeforePageDisplay implements HookListener {

	use OptionsAwareTrait;

	/**
	 * @since 3.1
	 *
	 * @param OutputPage $outputPage
	 */
	public function informAboutExtensionAvailability( OutputPage $outputPage ) {

		if (
			$this->getOption( 'SMW_EXTENSION_LOADED' ) ||
			$this->getOption( 'smwgIgnoreExtensionRegistrationCheck' ) ) {
			return;
		}

		$title = $outputPage->getTitle();

		if ( $title === null || !$title->isSpecial( 'Version' ) ) {
			return;
		}

		$outputPage->prependHTML(
			'<div class="errorbox" style="display:block;">Semantic MediaWiki ' .
			'was installed but not enabled on this wiki. Please consult the ' .
			'<a href="https://www.semantic-mediawiki.org/wiki/Extension_registration">help page</a> for ' .
			'instructions and further assistances.</div>'
		);
	}

	/**
	 * @since 1.9
	 *
	 * @param OutputPage $outputPage
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

		if ( $this->getOption( 'incomplete_tasks', [] ) !== [] ) {
			$outputPage->prependHTML( $this->createIncompleteSetupTaskNotification( $title ) );
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

	private function createIncompleteSetupTaskNotification( $title ) {

		$disallowSpecialPages = [
			'Userlogin',
			'PendingTaskList',
			'CreateAccount'
		];

		if ( $title->isSpecialPage() ) {
			foreach ( $disallowSpecialPages as $specialPage ) {
				if ( $title->isSpecial( $specialPage ) ) {
					return '';
				}
			}
		}

		$is_upgrade = $this->getOption( 'is_upgrade' ) !== null ? 2 : 1;
		$count = count( $this->getOption( 'incomplete_tasks' ) );

		return Html::rawElement(
			'div',
			[
				'class' => 'smw-callout smw-callout-error plainlinks'
			],
			Html::rawElement(
				'div',
				[
					'style' => 'font-size: 10px;text-align: right;margin-top: 5px;margin-bottom: -5px; float:right;'
				],
				Message::get( [ 'smw-install-incomplete-intro-note' ], Message::PARSE, Message::USER_LANGUAGE )
			) . Html::rawElement(
				'div',
				[
					'class' => 'title'
				],
				Message::get( 'smw-title' )
			) .
			Message::get( [ 'smw-install-incomplete-intro', $is_upgrade, $count ], Message::PARSE, Message::USER_LANGUAGE )
		);
	}

}
