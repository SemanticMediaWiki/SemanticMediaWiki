<?php

namespace SMW\Indicator;

use SMW\DataItems\WikiPage;

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
	 * @param WikiPage $subject
	 * @param array $options
	 *
	 * @return bool
	 */
	public function hasIndicator( WikiPage $subject, array $options ): bool;

	/**
	 * @since 3.1
	 *
	 * @return
	 */
	public function getIndicators(): array;

	/**
	 * @since 3.1
	 *
	 * @return
	 */
	public function getModules(): array;

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
