<?php

namespace SMW\MediaWiki\Hooks;

use Hooks;
use User;
use Xml;
use SMW\Utils\Logo;
use SMW\MediaWiki\HookListener;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\GroupPermissions;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\HookDispatcherAwareTrait;
use SMW\OptionsAwareTrait;
use SMW\Schema\Compartment;
use SMW\MediaWiki\Specials\FacetedSearch\Profile;

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
class GetPreferences implements HookListener {

	use OptionsAwareTrait;
	use HookDispatcherAwareTrait;
	use MessageLocalizerTrait;

	/**
	 * Option to enable textinput suggester
	 */
	const ENABLE_ENTITY_SUGGESTER = 'smw-prefs-general-options-suggester-textinput';

	/**
	 * Option to enable jobqueue watchlist
	 */
	const VIEW_JOBQUEUE_WATCHLIST = 'smw-prefs-general-options-jobqueue-watchlist';

	/**
	 * Option to diable the edit page information on an edit form
	 */
	const DISABLE_EDITPAGE_INFO = 'smw-prefs-general-options-disable-editpage-info';

	/**
	 * Option to disable the search information on the `Special:Search` page
	 */
	const DISABLE_SEARCH_INFO = 'smw-prefs-general-options-disable-search-info';

	/**
	 * @var PermissionExaminer
	 */
	private $permissionExaminer;

	/**
	 * @since 3.2
	 *
	 * @param PermissionExaminer $permissionExaminer
	 */
	public function __construct( PermissionExaminer $permissionExaminer ) {
		$this->permissionExaminer = $permissionExaminer;
	}

	/**
	 * @since 2.0
	 *
	 * @param User $user
	 * @param array &$preferences
	 *
	 * @return true
	 */
	public function process( User $user, array &$preferences ) {

		$otherPreferences = [];
		$this->hookDispatcher->onGetPreferences( $user, $otherPreferences );
		$this->permissionExaminer->setUser( $user );

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

		if ( $this->permissionExaminer->hasPermissionOf( GroupPermissions::VIEW_JOBQUEUE_WATCHLIST ) ) {
			$preferences[self::VIEW_JOBQUEUE_WATCHLIST] = [
				'type' => 'toggle',
				'label-message' => 'smw-prefs-general-options-jobqueue-watchlist',
				'help-message' => 'smw-prefs-help-general-options-jobqueue-watchlist',
				'section' => 'smw/general-options',
				'disabled' => $this->getOption( 'smwgJobQueueWatchlist', [] ) === []
			];
		}

		$preferences[self::DISABLE_EDITPAGE_INFO] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-disable-editpage-info',
			'section' => 'smw/general-options',
			'disabled' => !$this->getOption( 'smwgEnabledEditPageHelp', false )
		];

		// Option to enable input assistance
		$preferences[self::ENABLE_ENTITY_SUGGESTER] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-suggester-textinput',
			'help-message' => 'smw-prefs-help-general-options-suggester-textinput',
			'section' => 'smw/general-options',
		];

		$preferences[self::DISABLE_SEARCH_INFO] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-disable-search-info',
			'section' => 'smw/extended-search-options',
			'disabled' => $this->getOption( 'wgSearchType' ) !== SMW_SPECIAL_SEARCHTYPE
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
