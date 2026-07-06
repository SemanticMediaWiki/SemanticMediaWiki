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
	 */
	public function hasIndicator( WikiPage $subject, array $options ): bool;

	/**
	 * @since 3.1
	 */
	public function getIndicators(): array;

	/**
	 * @since 3.1
	 */
	public function getModules(): array;

	/**
	 * @since 3.1
	 */
	public function getInlineStyle(): string;

	/**
	 * @since 3.2
	 */
	public function getName(): string;

}
