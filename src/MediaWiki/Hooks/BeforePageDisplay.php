<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Html\Html;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Skin\SkinComponentUtils;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use SMW\Localizer\Message;
use SMW\Settings;
use SMW\SetupFile;

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
class BeforePageDisplay implements BeforePageDisplayHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly UserOptionsLookup $userOptionsLookup,
		private readonly Settings $settings,
		private readonly SetupFile $setupFile,
	) {
	}

	/**
	 * Static handler used during extension boot to inform the user when
	 * the extension was loaded but not enabled.
	 *
	 * @since 7.0.0
	 */
	public static function informAboutExtensionAvailability( OutputPage $outputPage ): void {
		if (
			defined( 'SMW_EXTENSION_LOADED' ) ||
			!empty( $GLOBALS['smwgIgnoreExtensionRegistrationCheck'] ) ) {
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
	 * @since 7.0.0
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$title = $out->getTitle();
		$user = $out->getUser();
		// #2726
		if ( $this->userOptionsLookup->getOption( $user, 'smw-prefs-general-options-suggester-textinput' ) ) {
			$out->addModules( 'ext.smw.suggester.textInput' );
		}

		$incompleteTasks = $this->setupFile->findIncompleteTasks();
		$isUpgrade = $this->setupFile->get( SetupFile::PREVIOUS_VERSION );

		if ( $incompleteTasks !== [] ) {
			$out->addModuleStyles( [ 'mediawiki.codex.messagebox.styles' ] );
			$out->prependHTML( $this->createIncompleteSetupTaskNotification( $title, $incompleteTasks, $isUpgrade ) );
		}

		// Add export link to the head
		if (
			$this->settings->get( 'smwgEnableExportRDFLink' ) &&
			$title instanceof Title &&
			!$title->isSpecialPage()
		) {
			$link = [];
			$link['rel']   = 'alternate';
			$link['type']  = 'application/rdf+xml';
			$link['title'] = $title->getPrefixedText();
			$link['href']  = SkinComponentUtils::makeSpecialUrl( 'ExportRDF', [ 'xmlmime' => 'rdf' ] );
			$out->addLink( $link );
		}
	}

	private function createIncompleteSetupTaskNotification( $title, array $incompleteTasks, $isUpgrade ): string {
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

		$isUpgradeFlag = $isUpgrade !== null ? 2 : 1;
		$count = count( $incompleteTasks );

		$titleHtml = Html::element( 'strong', [], Message::get( 'smw-title' ) );
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
			$titleHtml . $note
		);
		$content = Message::get( [ 'smw-install-incomplete-intro', $isUpgradeFlag, $count ], Message::PARSE, Message::USER_LANGUAGE );

		return Html::errorBox(
			$header .
			Html::rawElement( 'p', [], $content )
		);
	}
}
