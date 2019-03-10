<?php

namespace SMW\MediaWiki;

use Title;

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
	 * @param Title $title
	 *
	 * @return boolean
	 */
	public function hasIndicator( Title $title, $parserOutput );

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

}
