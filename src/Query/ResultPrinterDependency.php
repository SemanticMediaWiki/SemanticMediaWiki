<?php

namespace SMW\Query;

/**
 * Interface for result printers that require some dependency to be fully
 * functional.
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
interface ResultPrinterDependency {

	/**
	 * For result printers that implement this interface, return whether the
	 * dependency contract is fulfilled or is missing something and would
	 * impair the functionality of the result printer.
	 *
	 * This function will be called before any query execution ensuring that no
	 * system resources are depleted while the printer is missing required
	 * dependencies.
	 *
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function hasMissingDependency();

	/**
	 * Returns a desriptive error message in case of an unfulfilled dependency
	 * contract to help users to understand as to why the result printer doesn't
	 * return any results.
	 *
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getDependencyError();

}
