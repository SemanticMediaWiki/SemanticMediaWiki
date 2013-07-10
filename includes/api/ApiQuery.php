<?php

namespace SMW;

use SMWQueryProcessor;
use SMWQueryResult;
use SMWQuery;

/**
 * Base for API modules that query SMW.
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
 * Base for API modules that query SMW
 *
 * @ingroup Api
 */
abstract class ApiQuery extends ApiBase {

	/**
	 * Returns a query object for the provided query string and list of printouts.
	 *
	 * @since 1.6.2
	 *
	 * @param string $queryString
	 * @param array $printouts
	 * @param array $parameters
	 *
	 * @return SMWQuery
	 */
	protected function getQuery( $queryString, array $printouts, array $parameters = array() ) {

		SMWQueryProcessor::addThisPrintout( $printouts, $parameters );

		return SMWQueryProcessor::createQuery(
			$queryString,
			SMWQueryProcessor::getProcessedParams( $parameters, $printouts ),
			SMWQueryProcessor::SPECIAL_PAGE,
			'',
			$printouts
		);
	}

	/**
	 * Run the actual query and return the result.
	 *
	 * @since 1.6.2
	 *
	 * @param SMWQuery $query
	 *
	 * @return SMWQueryResult
	 */
	protected function getQueryResult( SMWQuery $query ) {
		 return $this->store->getQueryResult( $query );
	}

	/**
	 * Add the query result to the API output.
	 *
	 * @since 1.6.2
	 *
	 * @param SMWQueryResult $queryResult
	 */
	protected function addQueryResult( SMWQueryResult $queryResult ) {

		$result = $this->getResult();

		$resultFormatter = new ApiQueryResultFormatter( $queryResult );
		$resultFormatter->setIsRawMode( $result->getIsRawMode() );
		$resultFormatter->setFormat( $result->getMain()->getPrinter() !== null ? $result->getMain()->getPrinter()->getFormat() : null );
		$resultFormatter->doFormat();

		if ( $resultFormatter->getContinueOffset() ) {
			$result->disableSizeCheck();
			$result->addValue( null, 'query-continue-offset', $resultFormatter->getContinueOffset() );
			$result->enableSizeCheck();
		}

		$result->addValue( null, $resultFormatter->getType(), $resultFormatter->getResult() );
	}
}
