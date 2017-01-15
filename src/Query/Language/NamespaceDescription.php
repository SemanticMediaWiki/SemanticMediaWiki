<?php

namespace SMW\Query\Language;

use SMW\Localizer;

/**
 * Description of all pages within a given wiki namespace, given by a numerical
 * constant. Corresponds to a class restriction with a special class that
 * characterises the given namespace (or at least that is how one could map
 * this to OWL etc.).
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class NamespaceDescription extends Description {

	/**
	 * @var integer
	 */
	private $namespace;

	/**
	 * @param integer $namespace
	 */
	public function __construct( $namespace ) {
		$this->namespace = $namespace;
	}

	/**
	 * @see Description::getFingerprint
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getFingerprint() {
		// Avoid a simple `int` which may interfere with an associative array
		// when compounding hash strings from different descriptions
		return 'N:' . md5( $this->namespace );
	}

	/**
	 * @return integer
	 */
	public function getNamespace() {
		return $this->namespace;
	}

	public function getQueryString( $asValue = false ) {

		$localizedNamespaceText = Localizer::getInstance()->getNamespaceTextById( $this->namespace );

		$prefix = $this->namespace == NS_CATEGORY ? ':' : '';

		if ( $asValue ) {
			return ' <q>[[' . $prefix . $localizedNamespaceText . ':+]]</q> ';
		}

		return '[[' . $prefix . $localizedNamespaceText . ':+]]';
	}

	public function isSingleton() {
		return false;
	}

	public function getQueryFeatures() {
		return SMW_NAMESPACE_QUERY;
	}

}
