<?php

/**
 * Base for export result printers.
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
 * @since 1.8
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SMWExportPrinter extends SMWResultPrinter implements SMWIExportPrinter {

	/**
	 * @see SMWIResultPrinter::isExportFormat
	 *
	 * @since 1.8
	 *
	 * @return boolean
	 */
	public final function isExportFormat() {
		return true;
	}

	/**
	 * @see SMWIExportPrinter::outputAsFile
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 * @param array $params
	 */
	public function outputAsFile( SMWQueryResult $queryResult, array $params ) {
		$result = $this->getResult( $queryResult, $params, SMW_OUTPUT_FILE );

		header( 'Content-type: ' . $this->getMimeType( $queryResult ) . '; charset=UTF-8' );

		$fileName = $this->getFileName( $queryResult );

		if ( $fileName !== false ) {
			header( "content-disposition: attachment; filename=$fileName" );
		}

		echo $result;
	}

	/**
	 * @see SMWIExportPrinter::getFileName
	 *
	 * @since 1.8
	 *
	 * @param SMWQueryResult $queryResult
	 *
	 * @return string|boolean
	 */
	public function getFileName( SMWQueryResult $queryResult ) {
		return false;
	}

}
