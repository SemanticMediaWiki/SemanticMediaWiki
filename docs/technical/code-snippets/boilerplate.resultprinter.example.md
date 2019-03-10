This document contains an example for a `ResultPrinter` both for the PHP and JavaScript part and before diving into the details, please make sure you have read ["Writing a result printer"](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/hacking/writing.resultprinter.md).

## PHP

<pre>
namespace SMW\Query\ResultPrinters;

use SMWQueryResult as QueryResult;
use SMWDataItem as DataItem;
use SMWDataValue as DataValue;
use Html;

/**
 * Boilerplate query printer
 *
 * Add your description here ...
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class BoilerplateResultPrinter extends ResultPrinter {

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		// Add your result printer name here
		return wfMessage( 'foo-boilerplate' )->text();
	}

	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {
		$definitions = parent::getParamDefinitions( $definitions );

		// Add your parameters here

		// Example of a unit paramter
		$definitions['unit'] = [
			'message' => 'foo-paramdesc-unit',
			'default' => '',
		];

		return $definitions;
	}

	/**
	 * @see ResultPrinter::getResources
	 *
	 * {@inheritDoc}
	 */
	protected function getResources() {

		// Add resource definitions that has been registered with `Resource.php`
		// Resource definitions contain scripts, styles, messages etc.

		return [
			'modules' => [
				'foo.boilerplate'
			],
			'styles' => [
				'foo.boilerplate.styles'
			]
		];
	}

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $queryResult, $outputMode ) {

		// Data processing
		// It is advisable to separate data processing from output logic
		$data = $this->preprocess( $queryResult, $outputMode );

		// Check if the data processing returned any results otherwise just bailout
		if ( $data === [] ) {
			// Add an error message to return method
			return $queryResult->addErrors( 'some-error' );
		} else {
			// Add options if needed to format the output

			// $outputMode can be specified as
			// SMW_OUTPUT_HTML
			// SMW_OUTPUT_FILE
			// SMW_OUTPUT_WIKI

			// For implementing template support this options has to be set but if you
			// manipulate data via jQuery/JavaScript it is less likely that you need
			// this option since templates will influence how wiki text is parsed
			// but will have no influence in how a HTML representation is altered
			// $this->hasTemplates = true;

			$options = [
				'mode' => $outputMode
			];

			// Return formatted results
			return $this->buildHTML( $data, $options );
		}
	}

	/**
	 * Returns an array with data
	 *
	 * @return array
	 */
	private function preprocess( QueryResult $queryResult, $outputMode ) {

		$data = [];

		// This is an example implementation on how to select available data from
		// a result set. Please make appropriate adoptions necessary for your
		// application.

		// Some methods are declared as private to show case which objects are
		// directly accessible within SMWQueryResult

		// Get all SMWDIWikiPage objects that make up the results
		// $subjects = $this->getSubjects( $queryResult->getResults() );

		// Get all print requests property labels
		// $labels = $this->getLabels( $queryResult->getPrintRequests() );

		/**
		 * Get all values for all rows that belong to the result set
		 *
		 * @var ResultArray $rows
		 */
		while ( $rows = $queryResult->getNext() ) {

			/**
			 * @var ResultArray $field
			 * @var DataValue $dataValue
			 */
			foreach ( $rows as $field ) {

				// Initialize the array each time it passes a new row to avoid data from
				// a previous row is remaining
				$rowData = [];

				// Get the label for the current property
				$propertyLabel = $field->getPrintRequest()->getLabel();

				// Get the label for the current subject
				// getTitle()->getText() will return only the main text without the
				// fragment(#) which can be arbitrary in case subobjects are involved

				// getTitle()->getFullText() will return the text with the fragment(#)
				// which is important when using subobjects
				$subjectLabel = $field->getResultSubject()->getTitle()->getFullText();

				while ( ( $dataValue = $field->getNextDataValue() ) !== false ) {

					// Get the data value item
					$rowData[] = $this->getDataValueItem( $dataValue->getDataItem()->getDIType(), $dataValue );
				}

				// Example how to build a hierarchical array by collecting all values
				// belonging to one subject/row using labels as array key representation
				$data[$subjectLabel][$propertyLabel][] = $rowData;
			}
		}

		// Return the data
		// return array( 'labels' => $labels, 'subjects' => $subjects, 'data' => $data );
		return $data;
	}

	/**
	 * A quick getway method to find all SMWDIWikiPage objects that make up the
	 * results
	 *
	 * @return array
	 */
	private function getSubjects( $result ) {
		$subjects = [];

		foreach ( $result as $wikiDIPage ) {
			$subjects[] = $wikiDIPage->getTitle()->getText();
		}
		return $subjects;
	}

	/**
	 * Get all print requests property labels
	 *
	 * @return array
	 */
	private function getLabels( $result ) {
		$printRequestsLabels = [];

		foreach ( $result as $printRequests ) {
			$printRequestsLabels[] = $printRequests->getLabel();
		}
		return $printRequestsLabels;
	}

	/**
	 * Get a single data value item
	 *
	 * @return mixed
	 */
	private function getDataValueItem( $type, DataValue $dataValue ) {

		if ( $type == DataItem::TYPE_NUMBER ) {

			// Set unit if available
			$dataValue->setOutputFormat( $this->params['unit'] );

			// Check if unit is available and return the converted value otherwise
			// just return a plain number
			if ( $dataValue->getUnit() !== '' ) {
				return $dataValue->getShortWikiText();
			}

			return $dataValue->getNumber();
		}

		// For all other data types return the wikivalue
		return $dataValue->getWikiValue();
	}

	/**
	 * Prepare data for the output
	 *
	 * @return string
	 */
	protected function buildHTML( $data, $options ) {

		// The generated ID is to distinguish similar instances of the same
		// printer that can appear within the same page
		$id = uniqid( 'foo-boilerplate-' . rand( 1, 10000 ) );

		// Used to set that the output and being treated as HTML (opposed to plain wiki text)
		$this->isHTML = true;

		// Correct escaping is vital to minimize possibilites of malicious code snippets
		// and also a coherent string evalution therefore it is recommended
		// that data transferred to the JS plugin is JSON encoded

		// Assign the ID to make a data instance readly available and distinguishable
		// from other content within the same page
		$requireHeadItem = [ $id => json_encode( $data ) ];
		\SMWOutputs::requireHeadItem( $id, \Skin::makeVariablesScript( $requireHeadItem ) );

		// Add two elements a outer wrapper that is assigned a class which the JS plugin
		// can select and will fetch all instances of the same result printer and an innner
		// container which is set invisible (display=none) for as long as the JS plugin
		// holds the content hidden. It is normally the place where the "hard work"
		// is done hidden from the user until it is ready.
		// The JS plugin can prepare the output within this container without presenting
		// unfinished visual content, to avoid screen clutter and improve user experience.
		return Html::rawElement(
			'div',
			[
				'class' => 'foo-boilerplate'
			],
			Html::element(
				'div',
				[
					'id' => $id,
					'class' => 'container',
					'style' => 'display:none;'
				]
			)
		);
	}

}
</pre>

## JavaScript

<pre>
/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
( function( $, mw ) {

	// Use EcmaScript 5 to improve code quality and check with jshint/jslint
	// if the code adheres standard coding conventions

	// Strict mode eliminates some JavaScript pitfalls
	'use strict';

	// Passing jshint
	/*global mediaWiki:true */

	/**
	 * @type Object
	 */
	foo = {};

	/**
	 * Base constructor for objects representing a boilerplate instance
	 *
	 * @type Object
	 */

	// If you have default values to be set during the instantiation
	// $.extend ... can be used here
	foo.boilerplate = function() {};

	foo.boilerplate.prototype = {
		// Specify your functions and parameters
		show: function( context ) {
			return context.each( function() {

				// Ensure variables have only local scope otherwise leaked content might
				// cause issues for other plugins
				var that = $( this );

				// Find the container instance that was created by the PHP output
				// and store it as "container" variable which all preceding steps
				// working on a localized instance
				var container = that.find( '.container' );

				// Find the ID that connects to the current instance with the published data
				var id = container.attr( 'id' );

				// Fetch the stored data with help of mw.config.get() method and the current instance ID
				// @see http://www.mediawiki.org/wiki/ResourceLoader/Default_modules#mediaWiki.config
				var json = mw.config.get( id );

				// Parse the fetched json string and convert it back into objects/arrays
				var data = typeof json === 'string' ? jQuery.parseJSON( json ) : json;

				// You got everything you need to work your magic
				// A clean instance, data from the wiki, and a separate container

				// If you need to see what data you've got from your result printer
				// it is always helpfull to do

				// console.log( data );

				// Happy coding ...
			} );
		}
	};

	/**
	 * Implementation and representation of the boilerplate instance
	 *
	 * @type Object
	 */

	// Create class instance
	var boilerplate = new foo.boilerplate();

	$( document ).ready(function() {

		// Use the class selector to find all instances relevant to the "boilerplate" printer
		// since a wiki page can have more than one instance of the same result printer
		// .each() ensures instances are handled separately
		$( '.foo-boilerplate' ).each(function() {

			// Access methods available through the boilerplate class
			boilerplate.show( $( this ) );
		} );
	} );

} )( jQuery, mediaWiki );
</pre>