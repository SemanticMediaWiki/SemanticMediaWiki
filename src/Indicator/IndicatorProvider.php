<?php

namespace SMW\Indicator;

use SMW\DIWikiPage;

/**
 * @license GPL-2.0-or-later
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
	 * @return bool
	 */
	public function hasIndicator( DIWikiPage $subject, array $options );

	/**
	 * @since 3.1
	 *
	 * @return
	 */
	public function getIndicators();

	/**
	 * @since 3.1
	 *
	 * @return
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
	public function getName(): string;

}
