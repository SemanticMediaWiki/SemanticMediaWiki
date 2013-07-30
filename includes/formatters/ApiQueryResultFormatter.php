<?php

namespace SMW;

use InvalidArgumentException;
use SMWQueryResult;

/**
 * This class handles the API related formatting of query results
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * This class handles the API related formatting of query results
 *
 * @ingroup Formatter
 */
final class ApiQueryResultFormatter {

	/** @var Integer|boolean */
	protected $continueOffset = false;

	/** @var String */
	protected $type;

	/** @var String|null */
	protected $format = null;

	/** @var Boolean */
	protected $isRawMode = false;

	/**
	 * @since 1.9
	 *
	 * @param SMWQueryResult $queryResult
	 */
	public function __construct( SMWQueryResult $queryResult ) {
		$this->queryResult = $queryResult;
	}

	/**
	 * Formatting a result array to support JSON/XML standards
	 *
	 * @since 1.9
	 *
	 * @param array $queryResult
	 *
	 * @return array
	 */
	public function formatResults( array $queryResult ) {

		$this->type = 'query';
		$results    = array();

		if ( $this->isXml() ) {

			foreach ( $queryResult['results'] as $subjectName => $subject ) {
				$serialized = array();

				foreach ( $subject as $key => $value ) {

					if ( $key === 'printouts' ) {
						$printouts = array();

						foreach ( $subject['printouts'] as $property => $values ) {

							if ( (array)$values === $values ) {
								$this->setIndexedTagName( $values, 'value' );
								$printouts[] = array_merge( array( 'label' => $property ), $values );
							}

						}

						$serialized['printouts'] = $printouts;
						$this->setIndexedTagName( $serialized['printouts'], 'property' );

					} else {
						$serialized[$key] = $value;
					}
				}

				$results[] = $serialized;
			}

			if ( $results !== array() ) {
				$queryResult['results'] = $results;
				$this->setIndexedTagName( $queryResult['results'], 'subject' );
			}

			$this->setIndexedTagName( $queryResult['printrequests'], 'printrequest' );
			$this->setIndexedTagName( $queryResult['meta'], 'meta' );

		};

		return $queryResult;
	}

	/**
	 * Formatting an error array in order to support JSON/XML
	 *
	 * @since 1.9
	 *
	 * @param array $errors
	 *
	 * @return array
	 */
	public function formatErrors( array $errors ) {

		$this->type      = 'error';
		$result['query'] = $errors;

		if ( $this->isXml() ) {
			$this->setIndexedTagName( $result['query'], 'info' );
		}

		return $result;
	}

	/**
	 * Sets the printer format and is mainly used for
	 * ApiQueryResultFormatter::isXml
	 *
	 * @since 1.9
	 *
	 * @param string $format
	 */
	public function setFormat( $format ) {
		$this->format = strtolower( $format );
	}

	/**
	 * Sets whether the formatter requested raw data and is used in connection
	 * with ApiQueryResultFormatter::setIndexedTagName
	 *
	 * @see ApiResult::getIsRawMode
	 *
	 * @since 1.9
	 *
	 * @param boolean $isRawMode
	 */
	public function setIsRawMode( $isRawMode ) {
		$this->isRawMode = $isRawMode;
	}

	/**
	 * Add '_element' to an array
	 *
	 * @note Copied from ApiResult::setIndexedTagName to avoid having a
	 * constructor injection in order to be able to access this method
	 *
	 * @see ApiResult::setIndexedTagName
	 *
	 * @since 1.9
	 *
	 * @param array &$arr
	 * @param string $tag
	 */
	public function setIndexedTagName( &$arr, $tag ) {

		if ( !$this->isRawMode ) {
			return;
		}

		if ( $arr === null || $tag === null || !is_array( $arr ) || is_array( $tag ) ) {
			throw new InvalidArgumentException( "{$tag} was incompatible with the requirements" );
		}

		$arr['_element'] = $tag;
	}

	/**
	 * Asserts if the current printer format is XML
	 *
	 * @see ApiMain::getPrinter()->getFormat()
	 *
	 * @since 1.9
	 *
	 * @return boolean
	 */
	public function isXml() {
		return $this->format === 'xml';
	}

	/**
	 * Returns an offset used for continuation support
	 *
	 * @since 1.9
	 *
	 * @return integer
	 */
	public function getContinueOffset() {
		return $this->continueOffset;
	}

	/**
	 * Returns the result type
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Returns formatted result
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getResult() {
		return $this->result;
	}

	/**
	 * Result formatting
	 *
	 * @since 1.9
	 */
	public function doFormat() {

		if ( $this->queryResult->getErrors() !== array() ) {
			$this->result = $this->formatErrors( $this->queryResult->getErrors() );
		} else {
			$this->result = $this->formatResults( $this->queryResult->toArray() );

			if ( $this->queryResult->hasFurtherResults() ) {
				$this->continueOffset = $this->result['meta']['count'] + $this->result['meta']['offset'];
			}
		}
	}
}
