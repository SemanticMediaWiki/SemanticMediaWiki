<?php

namespace SMW\Test;

use FauxRequest;
use RequestContext;
use ApiMain;

/**
 * Class contains Api related request methods
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
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group API
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Class contains Api related request methods
 *
 * @ingroup SMW
 */
abstract class ApiTestCase extends SemanticMediaWikiTestCase {

	/**
	 * Returns API request results
	 *
	 * The returned value is an array containing
	 * - the result data (array)
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	protected function doApiRequest( array $params ) {
		$request = new FauxRequest( $params, true );
		$context = RequestContext::getMain()->setRequest( $request );

		$api = new ApiMain( $context, true );
		$api->execute();
		return $api->getResultData();
	}

}
