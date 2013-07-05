<?php

namespace SMW\Test;

use SMW\ArrayAccessor;

/**
 * MockObject builder
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
 *
 * @license GNU GPL v2+
 */

/**
 * MockObject builder
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @codeCoverageIgnore
 */
class MockObjectBuilder extends \PHPUnit_Framework_TestCase {

	/** @var ArrayAccessor */
	protected $accessor;

	/**
	 * @since 1.9
	 *
	 * @param ArrayAccessor $accessor
	 */
	public function __construct( ArrayAccessor $accessor ) {
		$this->accessor = $accessor;
	}

	/**
	 * Check and return an invoked object
	 *
	 * @since 1.9
	 *
	 * @return mixed|null
	 */
	protected function get( $key ) {
		return $this->accessor->has( $key ) ? $this->accessor->get( $key ) : null;
	}

	/**
	 * Returns a SMWQueryResult object
	 *
	 * @since 1.9
	 *
	 * @return SMWQueryResult
	 */
	public function getQueryResult() {

		$queryResult = $this->getMockBuilder( 'SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->any() )
			->method( 'toArray' )
			->will( $this->returnValue( $this->get( 'toArray' ) ) );

		$queryResult->expects( $this->any() )
			->method( 'getErrors' )
			->will( $this->returnValue( $this->get( 'getErrors' ) ) );

		$queryResult->expects( $this->any() )
			->method( 'hasFurtherResults' )
			->will( $this->returnValue( $this->get( 'hasFurtherResults' ) ) );

		return $queryResult;
	}

}
