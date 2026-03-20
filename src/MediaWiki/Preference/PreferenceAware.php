<?php

namespace SMW\MediaWiki\Preference;

/**
 * Describes an instance that is aware of a user preference.
 *
 * @license GPL-2.0-or-later
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
	public function hasPreference( PreferenceExaminer $preferenceExaminer ): bool;

}
