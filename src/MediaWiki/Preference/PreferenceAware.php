<?php

namespace SMW\MediaWiki\Preference;

/**
 * Describes an instance that is aware of a user preference.
 *
 * @license GNU GPL v2
 * @since 3.2
 *
 * @author mwjames
 */
interface PreferenceAware {

	/**
	 * @since 3.2
	 *
	 * @param PreferenceExaminer $preferenceExaminer
	 *
	 * @return bool
	 */
	public function hasPreference( PreferenceExaminer $preferenceExaminer ) : bool;

}
