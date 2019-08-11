<?php

namespace SMW\MediaWiki\Hooks;

use Hooks;
use User;
use Xml;
use SMW\Utils\Logo;

/**
 * Hook: GetPreferences adds user preference
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class GetPreferences extends HookHandler {

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @since  2.0
	 *
	 * @param User $user
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * @since 2.0
	 *
	 * @param array &$preferences
	 *
	 * @return true
	 */
	public function process( array &$preferences ) {

		$otherPreferences = [];

		Hooks::run( 'SMW::GetPreferences', [ $this->user, &$otherPreferences ] );

		$html = $this->makeImage( Logo::get( '100x90' ) );
		$html .= wfMessage( 'smw-prefs-intro-text' )->parseAsBlock();

		// Intro text
		$preferences['smw-prefs-intro'] = [
			'type' => 'info',
			'label' => '&#160;',
			'default' => $html,
			'section' => 'smw',
			'raw' => 1
		];

		// Preference to allow time correction
		$preferences['smw-prefs-general-options-time-correction'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-time-correction',
			'section' => 'smw/general-options',
		];

		$preferences['smw-prefs-general-options-disable-editpage-info'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-disable-editpage-info',
			'section' => 'smw/general-options',
			'disabled' => !$this->getOption( 'smwgEnabledEditPageHelp', false )
		];

		$preferences['smw-prefs-general-options-disable-search-info'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-disable-search-info',
			'section' => 'smw/general-options',
			'disabled' => $this->getOption( 'wgSearchType' ) !== SMW_SPECIAL_SEARCHTYPE
		];

		$preferences['smw-prefs-general-options-jobqueue-watchlist'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-jobqueue-watchlist',
			'help-message' => 'smw-prefs-help-general-options-jobqueue-watchlist',
			'section' => 'smw/general-options',
			'disabled' => $this->getOption( 'smwgJobQueueWatchlist', [] ) === []
		];

		// Option to enable input assistance
		$preferences['smw-prefs-general-options-suggester-textinput'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-suggester-textinput',
			'help-message' => 'smw-prefs-help-general-options-suggester-textinput',
			'section' => 'smw/general-options',
		];

		// Option to enable tooltip info
		$preferences['smw-prefs-ask-options-tooltip-display'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-ask-options-tooltip-display',
			'section' => 'smw/ask-options',
		];

		$preferences += $otherPreferences;

		return true;
	}

	private function makeImage( $logo ) {
		return "<img style='float:right;margin-top: 10px;margin-left:20px;' src='{$logo}' height='63' width='70'>";
	}

}
