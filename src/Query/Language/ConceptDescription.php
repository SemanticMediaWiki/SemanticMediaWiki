<?php

namespace SMW\Query\Language;

use SMW\DataValueFactory;
use SMW\DIWikiPage;

/**
 * Description of a single class as described by a concept page in the wiki.
 * Corresponds to classes in (the EL fragment of) OWL DL, and to some extent to
 * tree-shaped queries in SPARQL.
 *
 * @license GNU GPL v2+
 * @since 1.6
 *
 * @author Markus Krötzsch
 */
class ConceptDescription extends Description {

	/**
	 * @var DIWikiPage
	 */
	private $concept;

	/**
	 * @param DIWikiPage $concept
	 */
	public function __construct( DIWikiPage $concept ) {
		$this->concept = $concept;
	}

	/**
	 * @return DIWikiPage
	 */
	public function getConcept() {
		return $this->concept;
	}

	public function getQueryString( $asValue = false ) {

		$pageValue = DataValueFactory::getInstance()->newDataValueByItem( $this->concept, null );
		$result = '[[' . $pageValue->getPrefixedText() . ']]';

		if ( $asValue ) {
			return ' <q>' . $result . '</q> ';
		}

		return $result;
	}

	public function isSingleton() {
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
