<?php

namespace SMW\Utils;

use SMW\DIWikiPage;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class Image {

	/**
	 * @see http://php.net/manual/en/function.image-type-to-extension.php
	 *
	 * @var []
	 */
	private static $images_types = [
		'gif' => 'image/gif',
		'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png' => 'image/png',
		'svg' => 'image/svg+xml',
		'swf' => 'application/x-shockwave-flash',
		'swc' => 'application/x-shockwave-flash',
		'psd' => 'image/psd',
		'bmp' => 'image/bmp',
		'jpc' => 'application/octet-stream',
		'jp2' => 'image/jp2',
		'jpf' => 'application/octet-stream',
		'jb2' => 'application/octet-stream',
		'xbm' => 'image/xbm',
		'tiff' => 'image/tiff',
		'aiff' => 'image/iff',
		'wbmp' => 'image/vnd.wap.wbmp'
	];

	/**
	 * @since 3.0
	 *
	 * @param DIWikiPage $dataItem
	 *
	 * @return boolean
	 */
	public static function isImage( DIWikiPage $dataItem ) {

		if ( $dataItem->getNamespace() !== NS_FILE || $dataItem->getSubobjectName() !== '' ) {
			return false;
		}

		$extension = strtolower(
			substr( strrchr( $dataItem->getDBKey(), "." ) , 1 )
			// pathinfo( $dataItem->getDBKey(), PATHINFO_EXTENSION )
		);

		return in_array( $extension, array_keys( self::$images_types ) );
	}

}
