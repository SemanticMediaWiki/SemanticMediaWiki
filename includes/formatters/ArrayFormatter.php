<?php

namespace SMW;

/**
 * Interface related to classes responsible for array formatting
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Interface related to classes responsible for array formatting
 *
 * @ingroup Formatter
 * @codeCoverageIgnore
 */
abstract class ArrayFormatter {

	/** @var array */
	protected $errors = array();

	/**
	 * Returns collected errors
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * Adds an error
	 *
	 * @since 1.9
	 *
	 * @param mixed $error
	 */
	public function addError( $error ) {
		$this->errors = array_merge( (array)$error === $error ? $error : array( $error ), $this->errors );
	}

	/**
	 * Returns a formatted array
	 *
	 * @since 1.9
	 *
	 * Implementation is carried out by a subclasses
	 */
	abstract public function toArray();
}
