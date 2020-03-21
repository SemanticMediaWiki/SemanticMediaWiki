<?php

namespace SMW\MediaWiki\Hooks;

use Hooks;
use User;
use Xml;
use SMW\Utils\Logo;
use SMW\MediaWiki\HookListener;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\HookDispatcherAwareTrait;
use SMW\OptionsAwareTrait;
use SMW\Schema\SchemaFactory;
use SMW\Schema\Compartment;
use SMW\MediaWiki\Specials\FacetedSearch\Profile as FacetedSearchProfile;
use SMW\MediaWiki\Specials\FacetedSearch\Exception\DefaultProfileNotFoundException;

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
	 * User specific default profile preference
	 */
	const FACETEDSEARCH_PROFILE_PREFERENCE = 'smw-prefs-factedsearch-profile';

	/**
	 * @var SchemaFactory
	 */
	private $schemaFactory;

	/**
	 * @since 2.0
	 *
	 * @param SchemaFactory $schemaFactory
	 */
	public function __construct( SchemaFactory $schemaFactory ) {
		$this->schemaFactory = $schemaFactory;
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

		$preferences['smw-prefs-general-options-jobqueue-watchlist'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-jobqueue-watchlist',
			'help-message' => 'smw-prefs-help-general-options-jobqueue-watchlist',
			'section' => 'smw/general-options',
			'disabled' => $this->getOption( 'smwgJobQueueWatchlist', [] ) === []
		];

		$preferences['smw-prefs-general-options-disable-editpage-info'] = [
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

		$preferences['smw-prefs-general-options-disable-search-info'] = [
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

		$preferences[self::FACETEDSEARCH_PROFILE_PREFERENCE] = [
			'type' => 'select',
			'section' => 'smw/ask-options',
			'label-message' => 'smw-prefs-factedsearch-profile',
			'options' => $this->getProfileList(),
			'default' => $user->getOption( 'smw-prefs-factedsearch-profile', 'default' ),
		];

		$preferences += $otherPreferences;

		return true;
	}

	private function makeImage( $logo ) {
		return "<img style='float:right;margin-top: 10px;margin-left:20px;' src='{$logo}' height='63' width='70'>";
	}

	private function getProfileList() : array {

		$facetedSearchProfile = new FacetedSearchProfile(
			$this->schemaFactory
		);

		try {
			$profileList = $facetedSearchProfile->getProfileList();
		} catch ( DefaultProfileNotFoundException $e ) {
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
