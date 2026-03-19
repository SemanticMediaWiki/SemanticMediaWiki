<?php

namespace SMW\Query\Language;

use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;

/**
 * Description of a single class as described by a concept page in the wiki.
 * Corresponds to classes in (the EL fragment of) OWL DL, and to some extent to
 * tree-shaped queries in SPARQL.
 *
 * @license GPL-2.0-or-later
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class ConceptDescription extends Description {

	public function __construct( private readonly WikiPage $concept ) {
	}

	/**
	 * @see Description::getFingerprint
	 * @since 2.5
	 *
	 * @return string
	 */
	public function getFingerprint(): string {
		return 'Co:' . md5( $this->concept->getHash() );
	}

	/**
	 * @return WikiPage
	 */
	public function getConcept() {
		return $this->concept;
	}

	public function getQueryString( $asValue = false ): string {
		$pageValue = DataValueFactory::getInstance()->newDataValueByItem( $this->concept, null );
		$result = '[[' . $pageValue->getPrefixedText() . ']]';

		if ( $asValue ) {
			return ' <q>' . $result . '</q> ';
		}

		return $result;
	}

	public function isSingleton(): bool {
		return false;
	}

	public function getQueryFeatures() {
		return SMW_CONCEPT_QUERY;
	}

	///NOTE: getSize and getDepth /could/ query the store to find the real size
	/// of the concept. But it is not clear if this is desirable anyway, given that
	/// caching structures may be established for retrieving concepts more quickly.
	/// Inspecting those would require future requests to the store, and be very
	/// store specific.
}
