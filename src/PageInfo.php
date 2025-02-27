<?php

namespace SMW;

/**
 * Facade interface to specify access to page information
 *
 * @ingroup SMW
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
interface PageInfo {

	/**
	 * Returns a modification date
	 *
	 * @since 1.9
	 *
	 * @return int
	 */
	public function getModificationDate();

	/**
	 * Returns a creation date
	 *
	 * @since 1.9
	 *
	 * @return int
	 */
	public function getCreationDate();

	/**
	 * Whether the page object is new or not
	 *
	 * @since 1.9
	 *
	 * @return bool
	 */
	public function isNewPage();

	/**
	 * Returns a user object for the last editor
	 *
	 * @since 1.9
	 *
	 * @return Title
	 */
	public function getLastEditor();

	/**
	 * @since 1.9.1
	 *
	 * @return bool
	 */
	public function isFilePage();

	/**
	 * @see File::getMediaType
	 *
	 * @since 1.9.1
	 *
	 * @return string|null
	 */
	public function getMediaType();

	/**
	 * @see File::getMimeType
	 *
	 * @since 1.9.1
	 *
	 * @return string|null
	 */
	public function getMimeType();

}
