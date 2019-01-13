<?php

namespace SMW\Query\Parser;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Message;
use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\ValueDescription;
use SMW\Site;
use SMWDataValue as DataValue;
use SMW\Query\QueryComparator;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 * @author Markus Krötzsch
 */
class DescriptionProcessor {

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory;

	/**
	 * @var DescriptionFactory
	 */
	private $descriptionFactory;

	/**
	 * @var integer
	 */
	private $queryFeatures;

	/**
	 * @var DIWikiPage|null
	 */
	private $contextPage;

	/**
	 * @var boolean
	 */
	private $selfReference = false;

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @since 2.4
	 *
	 * @param integer $queryFeatures
	 */
	public function __construct( $queryFeatures = false ) {
		$this->queryFeatures = $queryFeatures === false ? $GLOBALS['smwgQFeatures'] : $queryFeatures;
		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->descriptionFactory = new DescriptionFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @param DIWikiPage|null $contextPage
	 */
	public function setContextPage( DIWikiPage $contextPage = null ) {
		$this->contextPage = $contextPage;
	}

	/**
	 * @since 2.4
	 */
	public function clear() {
		$this->errors = [];
		$this->selfReference = false;
	}

	/**
	 * @since 2.4
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
	 */
	public function containsSelfReference() {
		return $this->selfReference;
	}

	/**
	 * @since 2.4
	 *
	 * @param array|string $error
	 */
	public function addError( $error ) {

		if ( !is_array( $error ) ) {
			$error = (array)$error;
		}

		if ( $error !== [] ) {
			$this->errors[] = Message::encode( $error );
		}
	}

	/**
	 * @since 2.4
	 *
	 * @param string $msgKey
	 */
	public function addErrorWithMsgKey( $msgKey /*...*/ ) {
		$this->errors[] = Message::encode( func_get_args() );
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 * @param string $chunk
	 *
	 * @return Description|null
	 */
	public function newDescriptionForPropertyObjectValue( DIProperty $property, $chunk ) {

		$dataValue = $this->dataValueFactory->newDataValueByProperty( $property );
		$dataValue->setContextPage( $this->contextPage );

		// Indicates whether a value is being used by a query condition or not which
		// can lead to a modified validation of a value.
		$dataValue->setOption( DataValue::OPT_QUERY_CONTEXT, true );
		$dataValue->setOption( 'isCapitalLinks', Site::isCapitalLinks() );

		$description = $dataValue->getQueryDescription( $chunk );
		$this->addError( $dataValue->getErrors() );

		return $description;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $chunk
	 *
	 * @return Description|null
	 */
	public function newDescriptionForWikiPageValueChunk( $chunk ) {

		// Only create a simple WpgValue to initiate the query description target
		// operation. If the chunk contains something like "≤Issue/1220" then the
		// WpgValue would return with an error as it cannot parse ≤ as/ legal
		// character, the chunk itself is processed by
		// DataValue::getQueryDescription hence no need to use it as input for
		// the factory instance
		$dataValue = $this->dataValueFactory->newTypeIDValue( '_wpg', 'QP_WPG_TITLE' );
		$dataValue->setContextPage( $this->contextPage );

		$dataValue->setOption( DataValue::OPT_QUERY_CONTEXT, true );

		// #3587
		// Requesting capital links is influenced by two factors, `wgCapitalLinks`
		// is enabled sitewide and the `WikiPageValue` condition is identified
		// as SMW_CMP_EQ/NEQ (e.g. [[Foo]], [[!Foo]]) with other expressions
		// (e.g. [[~foo*]]) to remain in the form of the user input
		$queryComparator = QueryComparator::getInstance();

		if ( Site::isCapitalLinks() && (
			$queryComparator->containsComparator( $chunk, SMW_CMP_EQ ) ||
			$queryComparator->containsComparator( $chunk, SMW_CMP_NEQ ) ) ) {
			$dataValue->setOption( 'isCapitalLinks', true );
		}

		$description = null;

		$description = $dataValue->getQueryDescription( $chunk );
		$this->addError( $dataValue->getErrors() );

		if ( !$this->selfReference && $this->contextPage !== null && $description instanceof ValueDescription ) {
			$this->selfReference = $description->getDataItem()->equals( $this->contextPage );
		}

		return $description;
	}

	/**
	 * The method was supposed to be named just `or` and `and` but this works
	 * only on PHP 7.1 therefore ...
	 */

	/**
	 * @since 2.4
	 *
	 * @param Description|null $currentDescription
	 * @param Description|null $newDescription
	 *
	 * @return Description|null
	 */
	public function asOr( Description $currentDescription = null, Description $newDescription = null ) {
		return $this->newCompoundDescription( $currentDescription, $newDescription, SMW_DISJUNCTION_QUERY );
	}

	/**
	 * @since 2.4
	 *
	 * @param Description|null $currentDescription
	 * @param Description|null $newDescription
	 *
	 * @return Description|null
	 */
	public function asAnd( Description $currentDescription = null, Description $newDescription = null ) {
		return $this->newCompoundDescription( $currentDescription, $newDescription, SMW_CONJUNCTION_QUERY );
	}

	/**
	 * Extend a given description by a new one, either by adding the new description
	 * (if the old one is a container description) or by creating a new container.
	 * The parameter $conjunction determines whether the combination of both descriptions
	 * should be a disjunction or conjunction.
	 *
	 * In the special case that the current description is NULL, the new one will just
	 * replace the current one.
	 *
	 * The return value is the expected combined description. The object $currentDescription will
	 * also be changed (if it was non-NULL).
	 */
	private function newCompoundDescription( Description $currentDescription = null, Description $newDescription = null, $compoundType = SMW_CONJUNCTION_QUERY ) {

		$notallowedmessage = 'smw_noqueryfeature';

		if ( $newDescription instanceof SomeProperty ) {
			$allowed = $this->queryFeatures & SMW_PROPERTY_QUERY;
		} elseif ( $newDescription instanceof ClassDescription ) {
			$allowed = $this->queryFeatures & SMW_CATEGORY_QUERY;
		} elseif ( $newDescription instanceof ConceptDescription ) {
			$allowed = $this->queryFeatures & SMW_CONCEPT_QUERY;
		} elseif ( $newDescription instanceof Conjunction ) {
			$allowed = $this->queryFeatures & SMW_CONJUNCTION_QUERY;
			$notallowedmessage = 'smw_noconjunctions';
		} elseif ( $newDescription instanceof Disjunction ) {
			$allowed = $this->queryFeatures & SMW_DISJUNCTION_QUERY;
			$notallowedmessage = 'smw_nodisjunctions';
		} else {
			$allowed = true;
		}

		if ( !$allowed ) {
			$this->addErrorWithMsgKey( $notallowedmessage, $newDescription->getQueryString() );
			return $currentDescription;
		}

		if ( $newDescription === null ) {
			return $currentDescription;
		} elseif ( $currentDescription === null ) {
			return $newDescription;
		} else { // we already found descriptions
			return $this->newCompoundDescriptionByType( $compoundType, $currentDescription, $newDescription );
		}
	}

	private function newCompoundDescriptionByType( $compoundType, $currentDescription, $newDescription ) {

		if ( ( ( $compoundType & SMW_CONJUNCTION_QUERY ) != 0 && ( $currentDescription instanceof Conjunction ) ) ||
		     ( ( $compoundType & SMW_DISJUNCTION_QUERY ) != 0 && ( $currentDescription instanceof Disjunction ) ) ) { // use existing container
			$currentDescription->addDescription( $newDescription );
			return $currentDescription;
		} elseif ( ( $compoundType & SMW_CONJUNCTION_QUERY ) != 0 ) { // make new conjunction
			return $this->newConjunction( $currentDescription, $newDescription );
		} elseif ( ( $compoundType & SMW_DISJUNCTION_QUERY ) != 0 ) { // make new disjunction
			return $this->newDisjunction( $currentDescription, $newDescription );
		}
	}

	private function newConjunction( $currentDescription, $newDescription ) {

		if ( $this->queryFeatures & SMW_CONJUNCTION_QUERY ) {
			return $this->descriptionFactory->newConjunction( [ $currentDescription, $newDescription ] );
		}

		$this->addErrorWithMsgKey( 'smw_noconjunctions', $newDescription->getQueryString() );

		return $currentDescription;
	}

	private function newDisjunction( $currentDescription, $newDescription ) {

		if ( $this->queryFeatures & SMW_DISJUNCTION_QUERY ) {
			return $this->descriptionFactory->newDisjunction( [ $currentDescription, $newDescription ] );
		}

		$this->addErrorWithMsgKey( 'smw_nodisjunctions', $newDescription->getQueryString() );

		return $currentDescription;
	}

}
