<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use SMW\GroupPermissions;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\MediaWiki\PermissionManager;
use SMW\MediaWiki\Specials\FacetedSearch\Exception\DefaultProfileNotFoundException;
use SMW\MediaWiki\Specials\FacetedSearch\Profile as FacetedSearchProfile;
use SMW\Schema\Exception\SchemaTypeNotFoundException;
use SMW\Schema\SchemaFactory;
use SMW\Settings;
use SMW\Utils\Logo;

/**
 * Hook: GetPreferences adds user preference
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class GetPreferences implements GetPreferencesHook {

	use MessageLocalizerTrait;

	/**
	 * Option to enable textinput suggester
	 */
	const ENABLE_ENTITY_SUGGESTER = 'smw-prefs-general-options-suggester-textinput';

	/**
	 * User specific default profile preference
	 */
	const FACETEDSEARCH_PROFILE_PREFERENCE = 'smw-prefs-factedsearch-profile';

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
	 * Option to disable the entity issue indicator and panel on a wiki page
	 */
	const SHOW_ENTITY_ISSUE_PANEL = 'smw-prefs-general-options-show-entity-issue-panel';

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly SchemaFactory $schemaFactory,
		private readonly HookContainer $hookContainer,
		private readonly Settings $settings,
		private readonly PermissionManager $permissionManager,
	) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$permissionExaminer = new PermissionExaminer( $this->permissionManager, $user );

		$otherPreferences = [];
		$this->hookContainer->run( 'SMW::GetPreferences', [ $user, &$otherPreferences ] );

		$html = $this->makeImage( Logo::get( 'small' ) );
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

		if ( $permissionExaminer->hasPermissionOf( GroupPermissions::VIEW_JOBQUEUE_WATCHLIST ) ) {
			$preferences[self::VIEW_JOBQUEUE_WATCHLIST] = [
				'type' => 'toggle',
				'label-message' => 'smw-prefs-general-options-jobqueue-watchlist',
				'help-message' => 'smw-prefs-help-general-options-jobqueue-watchlist',
				'section' => 'smw/general-options',
				'disabled' => ( $this->settings->get( 'smwgJobQueueWatchlist' ) ?: [] ) === []
			];
		}

		$preferences[self::DISABLE_EDITPAGE_INFO] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-disable-editpage-info',
			'section' => 'smw/general-options',
			'disabled' => !$this->settings->get( 'smwgEnabledEditPageHelp' )
		];

		// Option to enable input assistance
		$preferences[self::ENABLE_ENTITY_SUGGESTER] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-suggester-textinput',
			'help-message' => 'smw-prefs-help-general-options-suggester-textinput',
			'section' => 'smw/general-options',
		];

		$preferences[self::SHOW_ENTITY_ISSUE_PANEL] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-show-entity-issue-panel',
			'help-message' => 'smw-prefs-help-general-options-show-entity-issue-panel',
			'section' => 'smw/general-options',
		];

		$preferences[self::DISABLE_SEARCH_INFO] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-disable-search-info',
			'section' => 'smw/extended-search-options',
			'disabled' => $GLOBALS['wgSearchType'] !== SMW_SPECIAL_SEARCHTYPE
		];

		// Option to enable tooltip info
		$preferences['smw-prefs-ask-options-tooltip-display'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-ask-options-tooltip-display',
			'section' => 'smw/ask-options',
		];

		$preferences[self::FACETEDSEARCH_PROFILE_PREFERENCE] = [
			'type' => 'select',
			'section' => 'smw/ask-options',
			'label-message' => 'smw-prefs-factedsearch-profile',
			'options' => $this->getProfileList(),
			'default' => 'default',
		];

		$preferences += $otherPreferences;

		return true;
	}

	private function makeImage( ?string $logo ): string {
		return "<img style='float:right;margin-top:10px;margin-left:20px;height:auto;width:70px;' src='{$logo}'>";
	}

	private function getProfileList(): array {
		$facetedSearchProfile = new FacetedSearchProfile(
			$this->schemaFactory
		);

		try {
			$profileList = $facetedSearchProfile->getProfileList();
		} catch ( DefaultProfileNotFoundException | SchemaTypeNotFoundException ) {
			$profileList = [];
		}

		foreach ( $profileList as $name => $val ) {
			$label = $this->msg( $val );

			// Message contains itself, meaning label is unknown!
			if ( strpos( $label, $val ) !== false ) {
				$label = $name;
			}

			$profileList[$name] = $label;
		}

		$profileList = array_flip( $profileList );
		ksort( $profileList );

		return $profileList;
	}

}
