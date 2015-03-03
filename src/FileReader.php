<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
interface FileReader {

	/**
	 * @since 2.1
	 *
	 * @param string
	 */
	public function setFile( $file );

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function canRead();

	/**
	 * @since 2.1
	 *
	 * @return mixed
	 */
	public function read();

	/**
	 * @since 2.1
	 *
	 * @return integer
	 */
	public function getModificationTime();

}
