<?php

namespace MediaWiki\Json;

if( ! interface_exists( 'MediaWiki\Json\JsonUnserializable' ) ) {

	/**
	 * This interface was introduced in MediaWiki 1.36 and some classes need to implement it.
	 * With this stub definition SMW can be used on MediaWiki 1.35-.
	 *
	 * @license GNU GPL v2+
	 * @since 4.0.0
	 * @todo Remove when SMW will only support MediaWiki 1.36+ (see #5055).
	 *
	 * @author Sébastien Beyou
	 */
	interface JsonUnserializable {};

}
