<?php

namespace SMW\Query\DescriptionBuilders;

use SMW\ApplicationFactory;
use SMW\DataItemFactory;
use SMW\Query\DescriptionFactory;
use SMW\Query\QueryComparator;
use SMWDataValue as DataValue;
use SMW\DIProperty;

/**
 * @private
 *
 * Create an Description object based on a value string that was entered
 * in a query. Turning inputs that a user enters in place of a value within
 * a query string into query conditions is often a standard procedure. The
 * processing must take comparators like "<" into account, but otherwise
 * the normal parsing function can be used. However, there can be datatypes
 * where processing is more complicated, e.g. if the input string contains
 * more than one value, each of which may have comparators, as in
 * SMWRecordValue. In this case, it makes sense to overwrite this method.
 * Another reason to do this is to add new forms of comparators or new ways
 * of entering query conditions.
 *
 * The resulting Description may or may not make use of the datavalue
 * object that this function was called on, so it must be ensured that this
 * value is not used elsewhere when calling this method. The function can
 * return ThingDescription to not impose any condition, e.g. if parsing
 * failed. Error messages of this DataValue object are propagated.
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
abstract class DescriptionBuilder {

	/**
	 * @var DescriptionFactory
	 */
	protected $descriptionFactory;

	/**
	 * @var DataItemFactory
	 */
	protected $dataItemFactory;

	/**
	 * @var array
	 */
	protected $errors = [];

	/**
	 * @since 2.5
	 *
	 * @param DescriptionFactory|null $descriptionFactory
	 * @param DataItemFactory|null $dataItemFactory
	 */
	public function __construct( DescriptionFactory $descriptionFactory = null, DescriptionFactory $dataItemFactory = null ) {
		$this->descriptionFactory = $descriptionFactory;
		$this->dataItemFactory = $dataItemFactory;

		if ( $this->descriptionFactory === null ) {
			$this->descriptionFactory = ApplicationFactory::getInstance()->getQueryFactory()->newDescriptionFactory();
		}

		if ( $this->dataItemFactory === null ) {
			$this->dataItemFactory = ApplicationFactory::getInstance()->getDataItemFactory();
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param DataValue|null $dataValue
	 */
	public abstract function isBuilderFor( $dataValue );

	/**
	 * @since 2.3
	 *
	 * @param string $error
	 */
	public function addError( $error ) {

		if ( is_array( $error ) ) {
			return $this->errors = array_merge( $this->errors, $error );
		}

		$this->errors[] = $error;
	}

	/**
	 * @since 2.3
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @since 3.1
	 */
	public function clearErrors() {
		$this->errors = [];
	}

	/**
	 * Helper function for DescriptionDeserializer::deserialize that prepares a
	 * single value string, possibly extracting comparators. $value is changed
	 * to consist only of the remaining effective value string (without the
	 * comparator).
	 *
	 * @param string $value
	 * @param string|integer $comparator
	 */
	protected function prepareValue( DIProperty $property = null, &$value, &$comparator ) {
		$comparator = QueryComparator::getInstance()->extractComparatorFromString( $value );

		// [[in:lorem ipsum]] / [[Has text::in:lorem ipsum]] to be turned into a
		// proximity match where lorem AND ipsum needs to be present in the
		// indexed match field.
		//
		// For those query engines that support those search patterns!
		if ( $comparator === SMW_CMP_IN ) {
			$comparator = SMW_CMP_LIKE;

			// Looking for something like [[in:phrase:foo]]
			if ( strpos( $value, 'phrase:' ) !== false ) {
				$value = str_replace( 'phrase:', '', $value );
				$value = '"' . $value . '"';
			}

			// `in:...` is for the "busy" user to avoid adding wildcards now and
			// then to the value string
			$value = "*$value*";

			// No property and the assumption is [[in:...]] with the expected use
			// of the wide proximity as indicated by an additional `~`
			if ( $property === null ) {
				$value = "~$value";
			}
		}

		// [[not:foo bar]]
		// For those query engines that support those text search patterns!
		if ( $comparator === SMW_CMP_NOT ) {
			$comparator = SMW_CMP_NLKE;

			$value = str_replace( '!', '', $value );

			// Opposed to `in:` which includes *, `not:` is intended to match
			// only the exact entered term. It can be extended using *
			// if necessary (e.g. [[Has text::not:foo*]]).

			// Use as phrase to signal an exact term match for a wide proximity
			// search
			if ( $property === null ) {
				$value = "~\"$value\"";
			}
		}

		// [[phrase:lorem ipsum]] to be turned into a promixity phrase_match
		// where the entire string (incl. its order) are to be matched.
		//
		// For those query engines that support those search patterns!
		if ( $comparator === SMW_CMP_PHRASE ) {
			$comparator = SMW_CMP_LIKE;
			$value = '"' . $value . '"';

			if ( $property === null ) {
				$value = "~$value";
			}
		}
	}

}
