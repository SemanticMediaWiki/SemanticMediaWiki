<?php

namespace SMW;

use SMWQueryProcessor;

/**
 * API module to query SMW by providing a query in the ask language.
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
 * @since   1.6.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * API module to query SMW by providing a query in the ask language.
 *
 * @ingroup Api
 */
class ApiAsk extends ApiQuery {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {

		$parameterFormatter = new ApiRequestParameterFormatter( $this->extractRequestParams() );

		list( $queryString, $parameters, $printouts ) = SMWQueryProcessor::getComponentsFromFunctionParams( $parameterFormatter->getAskApiParameters(), false );

		$queryResult = $this->getQueryResult( $this->getQuery(
			$queryString,
			$printouts,
			$parameters
		) );

		$this->addQueryResult( $queryResult );
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getAllowedParams
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return array(
			'query' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
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
			'query' => 'The query string in ask-language'
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
			'API module to query SMW by providing a query in the ask language.'
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
			'api.php?action=ask&query=[[Modification%20date::%2B]]|%3FModification%20date|sort%3DModification%20date|order%3Ddesc',
		);
	}

	/**
	 * @codeCoverageIgnore
	 * @see ApiBase::getVersion
	 *
	 * @return string
	 */
	public function getVersion() {
		return __CLASS__ . '-' . SMW_VERSION;
	}

}
