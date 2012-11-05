<?php

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
 * @since 1.6.2
 *
 * @ingroup SMW
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ApiSMWQuery extends ApiBase {
	
	/**
	 * Query parameters.
	 * 
	 * @since 1.6.2
	 * @var array
	 */
	protected $parameters;
	
	/**
	 * Returns a query object for the provided query string and list of printouts.
	 *
	 * @since 1.6.2
	 *
	 * @param string $queryString
	 * @param array $printouts
	 *
	 * @return SMWQuery
	 */
	protected function getQuery( $queryString, array $printouts ) {
		SMWQueryProcessor::addThisPrintout( $printouts, $this->parameters );
		$this->parameters = SMWQueryProcessor::getProcessedParams( $this->parameters, $printouts );
		
		return SMWQueryProcessor::createQuery(
			$queryString,
			$this->parameters,
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
		 return smwfGetStore()->getQueryResult( $query );
	}

	/**
	 * Add the query result to the API output.
	 *
	 * @since 1.6.2
	 *
	 * @param SMWQueryResult $queryResult
	 */
	protected function addQueryResult( SMWQueryResult $queryResult ) {
		$serialized = $queryResult->serializeToArray();
		$result = $this->getResult();

		$result->setIndexedTagName( $serialized['results'], 'result' );
		$result->setIndexedTagName( $serialized['printrequests'], 'printrequest' );
		
		foreach ( $serialized['results'] as $subjectName => $subject ) {
			if ( is_array( $subject ) && array_key_exists( 'printouts', $subject ) ) {
				foreach ( $subject['printouts'] as $property => $values ) {
					if ( is_array( $values ) ) {
						$result->setIndexedTagName( $serialized['results'][$subjectName]['printouts'][$property], 'value' );
					}
				}
			}
		}
		
		$result->addValue( null, 'query', $serialized );
		
		if ( $queryResult->hasFurtherResults() ) {
			$result->disableSizeCheck();

			// TODO: right now this returns an offset that we can use for continuation, just like done
			// in other places in SMW. However, this is not efficient, so we should change this at some point.
			$result->addValue(
				null,
				'query-continue-offset',
				$this->parameters['offset']->getValue() + $queryResult->getCount()
			);

			$result->enableSizeCheck();
		}
	}

}
