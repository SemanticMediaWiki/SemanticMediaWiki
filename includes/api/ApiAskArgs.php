<?php

/**
 * API module to query SMW by providing a query specified as
 * a list of conditions, printouts and parameters. 
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
class ApiAskArgs extends ApiSMWQuery {
	
	public function execute() {
		$params = $this->extractRequestParams();

		foreach ( $params['parameters'] as $param ) {
			$parts = explode( '=', $param, 2 );
			
			if ( count( $parts ) == 2 ) {
				$this->parameters[$parts[0]] = $parts[1];
			}
		}
		
		$query = $this->getQuery( 
			implode( ' ', array_map( array( __CLASS__, 'wrapCondition' ), $params['conditions'] ) ),
			array_map( array( __CLASS__, 'printoutFromString' ), $params['printouts'] )
		);
		
		$this->addQueryResult( $this->getQueryResult( $query ) );
	}
	
	public static function wrapCondition( $c ) {
		return "[[$c]]"; 
	}
	
	public static function printoutFromString( $printout ) {
		return new SMWPrintRequest(
			SMWPrintRequest::PRINT_PROP,
			$printout,
			SMWPropertyValue::makeUserProperty( $printout )
		);
	}

	public function getAllowedParams() {
		return array(
			'conditions' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_REQUIRED => true,
			),
			'printouts' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
			),
			'parameters' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_DFLT => '',
				ApiBase::PARAM_ISMULTI => true,
			),
		);
	}
	
	public function getParamDescription() {
		return array(
			'conditions' => 'The query conditions, i.e. the requirements for a subject to be included',
			'printouts' => 'The query printouts, i.e. the properties to show per subject',
			'parameters' => 'The query parameters, i.e. all non-condition and non-printout arguments',
		);
	}
	
	public function getDescription() {
		return array(
			'API module to query SMW by providing a query specified as a list of conditions, printouts and parameters.'
		);
	}

	protected function getExamples() {
		return array(
			'api.php?action=askargs&conditions=Modification%20date::%2B&printouts=Modification%20date&parameters=|sort%3DModification%20date|order%3Ddesc',
		);
	}

	public function getVersion() {
		return __CLASS__ . '-' . SMW_VERSION;
	}
	
}
