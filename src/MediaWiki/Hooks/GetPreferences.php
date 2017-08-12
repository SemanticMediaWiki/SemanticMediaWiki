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
class GetPreferences extends HookHandler {

	/**
	 * @var User
	 */
	private $user;

	/**
	 * @var boolean
	 */
	private $enabledEditPageHelp = false;

	/**
	 * @since  2.0
	 *
	 * @param User $user
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	/**
	 * @since 3.0
	 *
	 * @param boolean $enabledEditPageHelp
	 */
	public function isEnabledEditPageHelp( $enabledEditPageHelp ) {
		$this->enabledEditPageHelp = (bool)$enabledEditPageHelp;
	}

	/**
	 * @since 2.0
	 *
	 * @param array &$preferences
	 *
	 * @return true
	 */
	public function process( array &$preferences ) {

		// Intro text
		$preferences['smw-prefs-intro'] =
			array(
				'type' => 'info',
				'label' => '&#160;',
				'default' => Xml::tags( 'tr', array(),
					Xml::tags( 'td', array( 'colspan' => 2 ),
						wfMessage(  'smw-prefs-intro-text' )->parseAsBlock() ) ),
				'section' => 'smw',
				'raw' => 1,
				'rawrow' => 1,
			);

		// Preference to allow time correction
		$preferences['smw-prefs-general-options-time-correction'] = array(
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-time-correction',
			'section' => 'smw/general-options',
		);

		$preferences['smw-prefs-general-options-disable-editpage-info'] = array(
			'type' => 'toggle',
			'label-message' => 'smw-prefs-general-options-disable-editpage-info',
			'section' => 'smw/general-options',
			'disabled' => !$this->enabledEditPageHelp
		);

		// Option to enable tooltip info
		$preferences['smw-prefs-ask-options-tooltip-display'] = array(
			'type' => 'toggle',
			'label-message' => 'smw-prefs-ask-options-tooltip-display',
			'section' => 'smw/ask-options',
		);

		return true;
	}

}
