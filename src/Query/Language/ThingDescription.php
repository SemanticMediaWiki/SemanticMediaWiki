<?php

namespace SMW\Query\Language;

/**
 * A dummy description that describes any object. Corresponds to
 * owl:thing, the class of all abstract objects. Note that it is
 * not used for datavalues of attributes in order to support type
 * hinting in the API: descriptions of data are always
 * ValueDescription objects.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class ThingDescription extends Description {

	public function getQueryString( $asValue = false ) {
		return $asValue ? '+' : '';
	}

	public function isSingleton() {
		return false;
	}

	public function getSize() {
		return 0; // no real condition, no size or depth
	}

	public function prune( &$maxsize, &$maxdepth, &$log ) {
		return $this;
	}

}
