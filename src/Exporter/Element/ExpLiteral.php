<?php

namespace SMW\Exporter\Element;

use InvalidArgumentException;
use RuntimeException;
use SMWDataItem as DataItem;

/**
 * A single datatype literal for export. Defined by a literal value and a
 * datatype URI.
 *
 * @todo Currently no support for language tags.
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class ExpLiteral extends ExpElement {

	/**
	 * Lexical form of the literal.
	 * @var string
	 */
	private $lexicalForm;

	/**
	 * Datatype URI for the literal.
	 * @var string
	 */
	private $datatype;

	/**
	 * @var string
	 */
	private $lang = '';

	/**
	 * @note The given lexical form should be the plain string for
	 * representing the literal without datatype or language information.
	 * It must not use any escaping or abbreviation mechanisms.
	 *
	 * @param string $lexicalForm lexical form
	 * @param string $datatype Data type URI or empty for untyped literals
	 * @param string $lang
	 * @param DataItem|null $dataItem
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $lexicalForm, $datatype = '', $lang = '', DataItem $dataItem = null ) {

		if ( !is_string( $lexicalForm ) ) {
			throw new InvalidArgumentException( '$lexicalForm needs to be a string' );
		}

		if ( !is_string( $datatype ) ) {
			throw new InvalidArgumentException( '$datatype needs to be a string' );
		}

		// http://www.w3.org/TR/rdf11-concepts/#section-Graph-Literal
		if ( !is_string( $lang ) || ( $lang !== '' && $datatype !== 'http://www.w3.org/1999/02/22-rdf-syntax-ns#langString' ) ) {
			throw new InvalidArgumentException( '$lang needs to be a string and $datatype has to be of langString type' );
		}

		parent::__construct( $dataItem );

		$this->lexicalForm = $lexicalForm;
		$this->datatype = $datatype;
		$this->lang = $lang;
	}

	/**
	 * Returns a language tag with the language tag must be well-formed according
	 * to BCP47
	 *
	 * @return string
	 */
	public function getLang() {
		return $this->lang;
	}

	/**
	 * Return the URI of the datatype used, or the empty string if untyped.
	 *
	 * @return string
	 */
	public function getDatatype() {
		return $this->datatype;
	}

	/**
	 * Return the lexical form of the literal. The result does not use
	 * any escapings and might still need to be escaped in some contexts.
	 * The lexical form is not validated or canonicalized.
	 *
	 * @return string
	 */
	public function getLexicalForm() {
		return $this->lexicalForm;
	}

	/**
	 * @since  2.2
	 *
	 * @return array
	 */
	public function getSerialization() {

		$serialization = array(
			'type'     => self::TYPE_LITERAL,
			'lexical'  => $this->lexicalForm,
			'datatype' => $this->datatype,
			'lang'     => $this->lang
		);

		return $serialization + parent::getSerialization();
	}

	/**
	 * @see ExpElement::newFromSerialization
	 */
	protected static function deserialize( $serialization ) {

		if ( !isset( $serialization['lexical'] ) || !isset( $serialization['datatype'] ) || !isset( $serialization['lang'] ) ) {
			throw new RuntimeException( "Invalid format caused by a missing lexical/datatype element" );
		}

		return new self(
			$serialization['lexical'],
			$serialization['datatype'],
			$serialization['lang'],
			$serialization['dataitem']
		);
	}

}
