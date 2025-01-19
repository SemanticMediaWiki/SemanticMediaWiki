<?php

namespace SMW\MediaWiki\Hooks;

use Html;
use OutputPage;
use Skin;
use SMW\MediaWiki\HookListener;
use SMW\Message;
use SMW\OptionsAwareTrait;
use SMW\Services\ServicesFactory;
use SpecialPage;
use Title;

/**
 * BeforePageDisplay hook which allows last minute changes to the
 * output page, e.g. adding of CSS or JavaScript
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
 *
 * @license GPL-2.0-or-later
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

		$outputPage->prependHTML( Html::errorBox(
			'Semantic MediaWiki was installed but not enabled on this wiki. ' .
			'Please consult the <a href="https://www.semantic-mediawiki.org/wiki/Extension_registration">help page</a> ' .
			'for instructions and further assistances.'
		) );
	}

	/**
	 * @since 1.9
	 *
	 * @param OutputPage $outputPage
	 * @param Skin $skin
	 *
	 * @return bool
	 */
	public function process( OutputPage $outputPage, Skin $skin ) {
		$title = $outputPage->getTitle();
		$user = $outputPage->getUser();
		// #2726
		$userOptionsLookup = ServicesFactory::getInstance()->singleton( 'UserOptionsLookup' );
		if ( $userOptionsLookup->getOption( $user, 'smw-prefs-general-options-suggester-textinput' ) ) {
			$outputPage->addModules( 'ext.smw.suggester.textInput' );
		}

		if ( $this->getOption( 'incomplete_tasks', [] ) !== [] ) {
			$outputPage->prependHTML( $this->createIncompleteSetupTaskNotification( $title ) );
		}

		// Add export link to the head
		if (
			$this->getOption( 'smwgEnableExportRDFLink' ) &&
			$title instanceof Title &&
			!$title->isSpecialPage()
		) {
			$link['rel']   = 'alternate';
			$link['type']  = 'application/rdf+xml';
			$link['title'] = $title->getPrefixedText();
			$link['href']  = SpecialPage::getTitleFor( 'ExportRDF', $title->getPrefixedText() )->getLocalUrl( 'xmlmime=rdf' );
			$outputPage->addLink( $link );
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

		// TODO: Refactor message content HTML generation into Mustache or another class
		$title = Html::rawElement( 'strong', [], Message::get( 'smw-title' ) );
		$note = Html::rawElement( 'span',
			[
				'style' => 'color: var( --color-subtle, #54595d ); font-size: 0.75rem;'
			],
			Message::get( [ 'smw-install-incomplete-intro-note' ], Message::PARSE, Message::USER_LANGUAGE )
		);
		$header = Html::rawElement( 'div',
			[
				'style' => 'display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 0.25rem 0.5rem;'
			],
			$title . $note
		);
		$content = Message::get( [ 'smw-install-incomplete-intro', $is_upgrade, $count ], Message::PARSE, Message::USER_LANGUAGE );

		return Html::errorBox(
			$header .
			Html::rawElement( 'p', [], $content )
		);
	}
}
