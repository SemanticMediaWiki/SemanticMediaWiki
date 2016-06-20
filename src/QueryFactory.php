<?php

namespace SMW;

use SMW\Query\DescriptionFactory;
use SMW\Query\Language\Description;
use SMW\Query\PrintRequestFactory;
use SMW\Query\ProfileAnnotatorFactory;
use SMWQuery as Query;
use SMWQueryParser as QueryParser;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class QueryFactory {

	/**
	 * @since 2.5
	 *
	 * @return ProfileAnnotatorFactory
	 */
	public function newProfileAnnotatorFactory() {
		return new ProfileAnnotatorFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @param Description $description
	 *
	 * @return Query
	 */
	public function newQuery( Description $description ) {
		return new Query( $description );
	}

	/**
	 * @since 2.4
	 *
	 * @return DescriptionFactory
	 */
	public function newDescriptionFactory() {
		return new DescriptionFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @return PrintRequestFactory
	 */
	public function newPrintRequestFactory() {
		return new PrintRequestFactory();
	}

	/**
	 * @since 2.4
	 *
	 * @return RequestOptions
	 */
	public function newRequestOptions() {
		return new RequestOptions();
	}

	/**
	 * @since 2.4
	 *
	 * @param string $string
	 * @param integer $condition
	 * @param boolean $isDisjunctiveCondition
	 *
	 * @return StringCondition
	 */
	public function newStringCondition( $string, $condition, $isDisjunctiveCondition = false ) {
		return new StringCondition( $string, $condition, $isDisjunctiveCondition );
	}

	/**
	 * @since 2.4
	 *
	 * @return QueryParser
	 */
	public function newQueryParser() {
		return new QueryParser();
	}

}
