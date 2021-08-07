<?php

namespace SMW\Tests\Utils\JSONScript;

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\PrintRequest as PrintRequest;
use SMW\Tests\Utils\UtilityFactory;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class QueryTestCaseInterpreter {

	/**
	 * @var array
	 */
	private $contents;

	/**
	 * @since 2.2
	 *
	 * @param array $contents
	 */
	public function __construct( array $contents ) {
		$this->contents = $contents;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function hasCondition() {
		return isset( $this->contents['condition'] );
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getCondition() {
		return $this->hasCondition() ? $this->contents['condition'] : '';
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function isAbout() {
		return isset( $this->contents['about'] ) ? $this->contents['about'] : 'no description';
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getQueryMode() {
		return isset( $this->contents['parameters']['querymode'] ) ? constant( $this->contents['parameters']['querymode'] ) : \SMWQuery::MODE_INSTANCES;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getLimit() {
		return isset( $this->contents['parameters']['limit'] ) ? (int)$this->contents['parameters']['limit'] : 100;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getOffset() {
		return isset( $this->contents['parameters']['offset'] ) ? (int)$this->contents['parameters']['offset'] : 0;
	}

	/**
	 * @since 2.5
	 *
	 * @return DIWikiPage|null
	 */
	public function getSubject() {
		return isset( $this->contents['subject'] ) ? DIWikiPage::newFromText( $this->contents['subject'] ) : null;
	}

	/**
	 * @since 2.5
	 *
	 * @return boolean
	 */
	public function isFromCache() {
		return isset( $this->contents['assert-queryresult']['isFromCache'] ) ? (bool)$this->contents['assert-queryresult']['isFromCache'] : null;
	}

	/**
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function checkSorting() {
		return isset( $this->contents['assert-queryresult']['check-sorting'] ) ? (bool)$this->contents['assert-queryresult']['check-sorting'] : false;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getExtraPrintouts() {

		$extraPrintouts = [];

		if ( !isset( $this->contents['printouts'] ) || $this->contents['printouts'] === [] ) {
			return $extraPrintouts;
		}

		foreach ( $this->contents['printouts'] as $printout ) {

			$parameters = [];
			$label = $printout;

			if ( is_array( $printout ) ) {
				$label = array_shift( $printout );
				$parameters = $printout;
			}

			if ( $label[0] === '_' ) {
				$printRequest = new PrintRequest(
					PrintRequest::PRINT_PROP,
					null,
					DataValueFactory::getInstance()->newPropertyValueByLabel( $label )
				);
			} else {
				$printRequest = PrintRequest::newFromText( $label );
			}

			foreach ( $parameters as $value ) {
				foreach ( $value as $k => $v) {
					$printRequest->setParameter( $k, $v );
				}
			}

			$extraPrintouts[] = $printRequest;
		}

		return $extraPrintouts;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getSortKeys() {

		if ( isset( $this->contents['parameters']['sort'] ) ) {

			if ( is_array( $this->contents['parameters']['sort'] ) ) {
				return $this->contents['parameters']['sort'];
			}

			return [ $this->contents['parameters']['sort'] => 'DESC' ];
		}

		return [];
	}

	/**
	 * @since 2.3
	 *
	 * @return boolean
	 */
	public function isRequiredToClearStoreCache() {
		return isset( $this->contents['store']['clear-cache'] ) && $this->contents['store']['clear-cache'];
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getExpectedCount() {
		return isset( $this->contents['assert-queryresult']['count'] ) ? (int)$this->contents['assert-queryresult']['count'] : 0;
	}

	/**
	 * @since 2.2
	 *
	 * @return DIWikiPage[]
	 */
	public function getExpectedSubjects() {

		$subjects = [];

		if ( !isset( $this->contents['assert-queryresult']['results'] )  ) {
			return $subjects;
		}

		foreach ( $this->contents['assert-queryresult']['results'] as $hashName ) {
			$subjects[] = DIWikiPage::doUnserialize( str_replace( ' ', '_', $hashName ) );
		}

		return $subjects;
	}

	/**
	 * @since 2.2
	 *
	 * @return DataItem[]
	 */
	public function getExpectedDataItems() {

		$dataItems = [];

		if ( !isset( $this->contents['assert-queryresult']['dataitems'] )  ) {
			return $dataItems;
		}

		foreach ( $this->contents['assert-queryresult']['dataitems'] as $dataitem ) {
			$dataItems[] = DataItem::newFromSerialization(
				DataTypeRegistry::getInstance()->getDataItemId( $dataitem['type'] ),
				$dataitem['value']
			);
		}

		return $dataItems;
	}

	/**
	 * @since 2.2
	 *
	 * @return DataValues[]
	 */
	public function getExpectedDataValues() {

		$dataValues = [];

		if ( !isset( $this->contents['assert-queryresult']['datavalues'] )  ) {
			return $dataValues;
		}

		foreach ( $this->contents['assert-queryresult']['datavalues'] as $datavalue ) {
			$dataValues[] = DataValueFactory::getInstance()->newDataValueByProperty(
				DIProperty::newFromUserLabel( $datavalue['property'] ),
				$datavalue['value']
			);
		}

		return $dataValues;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getExpectedErrorCount() {

		if ( !isset( $this->contents['assert-queryresult']['error'] ) ) {
			return -1;
		}

		return $this->contents['assert-queryresult']['error'];
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function fetchTextFromOutputSubject() {

		if ( !isset( $this->contents['subject'] ) ) {
			return '';
		}

		$title = \Title::newFromText( $this->contents['subject'] );
		$parserOutput = UtilityFactory::getInstance()->newPageReader()->getEditInfo( $title )->getOutput();

		return $parserOutput->getText();
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getExpectedFormatOuputFor( $id ) {

		$output = [];

		if ( !isset( $this->contents['assert-output'] ) || !isset( $this->contents['assert-output'][$id] )  ) {
			return $output;
		}

		$output = $this->contents['assert-output'][$id];

		// Concatenate to a string to ensure we keep the sequence as entered while
		// adding .* to signal that anything goes between them except for the string
		// expected to be asserted
		if ( isset( $this->contents['assert-output']['in-sequence' ] ) && is_array( $output ) ) {
			$output = implode( '.*', $output );
		}

		return $output;
	}

	/**
	 * @since 2.2
	 *
	 * @return []
	 */
	public function getExpectedConceptCache() {
		return isset( $this->contents['conceptcache'] ) ? $this->contents['conceptcache'] : [];
	}

}
