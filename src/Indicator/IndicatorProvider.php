<?php

namespace SMW\Indicator;

use SMW\DIWikiPage;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
interface IndicatorProvider {

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function hasIndicator( DIWikiPage $subject, array $options );

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getIndicators();

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getModules();

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getInlineStyle();

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getName() : string;

}
