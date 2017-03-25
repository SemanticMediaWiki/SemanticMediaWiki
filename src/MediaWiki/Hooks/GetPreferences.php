<?php

namespace SMW\MediaWiki\Hooks;

use User;
use Xml;

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
class GetPreferences {

	/**
	 * @var User
	 */
	private $user = null;

	/**
	 * @var array
	 */
	private $preferences;

	/**
	 * @since  2.0
	 *
	 * @param User $user
	 * @param array $preferences
	 */
	public function __construct( User $user, &$preferences ) {
		$this->user = $user;
		$this->preferences =& $preferences;
	}

	/**
	 * @since 2.0
	 *
	 * @return true
	 */
	public function process() {

		// Intro text
		$this->preferences['smw-prefs-intro'] =
			[
				'type' => 'info',
				'label' => '&#160;',
				'default' => Xml::tags( 'tr', [],
					Xml::tags( 'td', [ 'colspan' => 2 ],
						wfMessage(  'smw-prefs-intro-text' )->parseAsBlock() ) ),
				'section' => 'smw',
				'raw' => 1,
				'rawrow' => 1,
			];

		// Option to enable tooltip info
		$this->preferences['smw-prefs-ask-options-tooltip-display'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-ask-options-tooltip-display',
			'section' => 'smw/ask-options',
		];

		// Preference to set option box be collapsed by default
		$this->preferences['smw-prefs-ask-options-collapsed-default'] = [
			'type' => 'toggle',
			'label-message' => 'smw-prefs-ask-options-collapsed-default',
			'section' => 'smw/ask-options',
		];

		return true;
	}

}
