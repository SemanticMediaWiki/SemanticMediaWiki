<?php

namespace SMW\Tests\Fixtures\MediaWiki\Search;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DummySearchDatabase extends \SearchDatabase {

	/**
	 * @see SearchDatabase::doSearchTextInDB
	 *
	 * MW 1.32+
	 */
	protected function doSearchTextInDB( $term ) {
		return $term;
	}

	/**
	 * @see SearchDatabase::doSearchTitleInDB
	 *
	 * MW 1.32+
	 */
	protected function doSearchTitleInDB( $term ) {
		return $term;
	}

}
