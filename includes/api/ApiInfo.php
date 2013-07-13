<?php

namespace SMW;

/**
 * API module to obtain info about the SMW install, primarily targeted at
 * usage by the SMW registry.
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
 * @since   1.6
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * API module to obtain info about the SMW install, primarily targeted at
 * usage by the SMW registry.
 *
 * @ingroup Api
 */
class ApiInfo extends ApiBase {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$params = $this->extractRequestParams();
		$requestedInfo = $params['info'];
		$resultInfo = array();

		if ( in_array( 'propcount', $requestedInfo )
			|| in_array( 'usedpropcount', $requestedInfo )
			|| in_array( 'proppagecount', $requestedInfo )
			|| in_array( 'querycount', $requestedInfo )
			|| in_array( 'querysize', $requestedInfo )
			|| in_array( 'formatcount', $requestedInfo )
			|| in_array( 'conceptcount', $requestedInfo )
			|| in_array( 'subobjectcount', $requestedInfo )
			|| in_array( 'declaredpropcount', $requestedInfo ) ) {

			$semanticStats = $this->store->getStatistics();

			$map = array(
				'propcount' => 'PROPUSES',
				'usedpropcount' => 'USEDPROPS',
				'declaredpropcount' => 'DECLPROPS',
				'proppagecount' => 'OWNPAGE',
				'querycount' => 'QUERY',
				'querysize' => 'QUERYSIZE',
				'conceptcount' => 'CONCEPTS',
				'subobjectcount' => 'SUBOBJECTS',
			);

			foreach ( $map as $apiName => $smwName ) {
				if ( in_array( $apiName, $requestedInfo ) ) {
					$resultInfo[$apiName] = $semanticStats[$smwName];
				}
			}

			if ( in_array( 'formatcount', $requestedInfo ) ) {
				$resultInfo['formatcount'] = array();
				foreach ( $semanticStats['QUERYFORMATS'] as $name => $count ) {
					$resultInfo['formatcount'][$name] = $count;
				}
			}
		}

		$this->getResult()->addValue( null, 'info', $resultInfo );
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'info' => array(
				ApiBase::PARAM_DFLT => 'propcount|usedpropcount|declaredpropcount',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_TYPE => array(
					'propcount',
					'usedpropcount',
					'declaredpropcount',
					'proppagecount',
					'querycount',
					'querysize',
					'formatcount',
					'conceptcount',
					'subobjectcount'
				)
			),
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getParamDescription
	 *
	 * @return array
	 */
	public function getParamDescription() {
		return array(
			'info' => 'The info to provide.'
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getDescription
	 *
	 * @return array
	 */
	public function getDescription() {
		return array(
			'API module get info about this SMW install.'
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getExamples
	 *
	 * @return array
	 */
	protected function getExamples() {
		return array(
			'api.php?action=smwinfo&info=proppagecount|propcount',
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}

}
