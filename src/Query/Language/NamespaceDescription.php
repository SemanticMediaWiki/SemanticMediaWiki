<?php

namespace SMW\Query\Language;

use SMW\Localizer\Localizer;

/**
 * Description of all pages within a given wiki namespace, given by a numerical
 * constant. Corresponds to a class restriction with a special class that
 * characterises the given namespace (or at least that is how one could map
 * this to OWL etc.).
 *
 * @license GPL-2.0-or-later
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class NamespaceDescription extends Description {

	public function __construct( private $namespace ) {
	}

	/**
	 * @see Description::getFingerprint
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getFingerprint(): string {
		// Avoid a simple `int` which may interfere with an associative array
		// when compounding hash strings from different descriptions
		return 'N:' . md5( $this->namespace );
	}

	/**
	 * @return int
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	public function getQueryString( $asValue = false ): string {
		$localizedNamespaceText = Localizer::getInstance()->getNsText( $this->namespace );

		$prefix = $this->namespace == NS_CATEGORY ? ':' : '';

		if ( $asValue ) {
			return ' <q>[[' . $prefix . $localizedNamespaceText . ':+]]</q> ';
		}

		return '[[' . $prefix . $localizedNamespaceText . ':+]]';
	}

	public function isSingleton(): bool {
		return false;
	}

	public function getQueryFeatures(): int {
		return SMW_NAMESPACE_QUERY;
	}

}
