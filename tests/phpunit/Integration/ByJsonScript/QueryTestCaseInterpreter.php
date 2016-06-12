<?php

namespace SMW\Tests\Integration\ByJsonScript;

use SMW\DataTypeRegistry;
use SMW\DataValueFactory;
use SMW\DIWikiPage;
use SMW\Query\PrintRequest as PrintRequest;
use SMW\Tests\Utils\UtilityFactory;
use SMWDataItem as DataItem;
use SMWPropertyValue as PropertyValue;

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
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getExtraPrintouts() {

		$extraPrintouts = array();

		if ( !isset( $this->contents['printouts'] ) || $this->contents['printouts'] === array() ) {
			return $extraPrintouts;
		}

		foreach ( $this->contents['printouts'] as $printout ) {

			$label = null;

			if ( strpos( $printout, '#') !== false ) {
				list( $printout, $label ) = explode( '#', $printout );
			}

			$extraPrintouts[] = new PrintRequest(
				PrintRequest::PRINT_PROP,
				$label,
				PropertyValue::makeUserProperty( $printout )
			);
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

			return array( $this->contents['parameters']['sort'] => 'DESC' );
		}

		return array();
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
		return isset( $this->contents['queryresult']['count'] ) ? (int)$this->contents['queryresult']['count'] : 0;
	}

	/**
	 * @since 2.2
	 *
	 * @return DIWikiPage[]
	 */
	public function getExpectedSubjects() {

		$subjects = array();

		if ( !isset( $this->contents['queryresult']['results'] )  ) {
			return $subjects;
		}

		foreach ( $this->contents['queryresult']['results'] as $hashName ) {
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

		$dataItems = array();

		if ( !isset( $this->contents['queryresult']['dataitems'] )  ) {
			return $dataItems;
		}

		foreach ( $this->contents['queryresult']['dataitems'] as $dataitem ) {
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

		$dataValues = array();

		if ( !isset( $this->contents['queryresult']['datavalues'] )  ) {
			return $dataValues;
		}

		foreach ( $this->contents['queryresult']['datavalues'] as $datavalue ) {
			$dataValues[] = DataValueFactory::getInstance()->newDataValueByText(
				$datavalue['property'],
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

		if ( !isset( $this->contents['queryresult']['error'] )  ) {
			return 0;
		}

		return (int)$this->contents['queryresult']['error'];
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
		$parserOutput = UtilityFactory::getInstance()->newPageReader()->getEditInfo( $title )->output;

		return $parserOutput->getText();
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getExpectedFormatOuputFor( $id ) {

		$output = array();

		if ( !isset( $this->contents['expected-output'] ) || !isset( $this->contents['expected-output'][$id] )  ) {
			return $output;
		}


		return $this->contents['expected-output'][$id];
	}

	/**
	 * @since 2.2
	 *
	 * @return []
	 */
	public function getExpectedConceptCache() {
		return isset( $this->contents['conceptcache'] ) ? $this->contents['conceptcache'] : array();
	}

}
