This section explains how to create a new result printer to be used to visualize the result of a `#ask` query.

## Creating a printer class

Each result printer is implemented as a derived class from [`ResultPrinter.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/ResultPrinters/ResultPrinter.php) and will require at least to implement the following 3 methods:

<pre>
/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author ...
 */
class FooResultPrinter extends ResultPrinter {

	/**
	 * Output a human readable label for this printer.
	 *
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->msg( 'message-key-for-this-name' );
	}

	/**
	 * Defines the list of available parameters to an individual result
	 * printer.
	 *
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions[] = [
			'name' => 'foo',
			'message' => 'smw-paramdesc-foo',
			'default' => '',
		];

		return $definitions;
	}

	/**
	 * This method gets the query result object and is supposed to return
	 * whatever output the format creates. For example, in the list format, it
	 * goes through all results and constructs an HTML list, which is then
	 * returned. Looping through the result object is somewhat complex, and
	 * erquires some understanding of the `QueryResult` class.
	 *
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $queryResult, $outputMode ) {
		return '';
	}
}
</pre>

### Returning a name

Returns a human readable label for this printer.

<pre>
	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return $this->msg( 'message-key-for-this-name' );
	}
</pre>

### Handling parameters

Parameters passed to your result printer can be accessed via the `$params`field, which gets set by the base class before `ResultPrinter::getResultText` is called.

Fo example, if you want to retrived the value for parameter foobar, use `$this->params['foobar']`. It is '''not''' needed to check if these parameters are set, if they are of the right type, or adhere to any restrictions you might want to put on them. This will already have happened at this point in the base class.

- Invalid and non-set values will have been changed to their default.
- Invalid or missing required parameters would have caused an abort earlier on, so `ResultPrinter::getResultText` would not get called.
- When outputting any of these values, you will have to escape them using the core MediaWiki escaping functionality for security reasons.

The `ResultPrinter::getParamDefinitions` function returns the allowed parameters for a query that uses the specific format. It should return an array of Parameter objects. These define in a declarative fashion which parameters the result printer accepts, what their type is, and their default values. See [ParamProcessor](https://github.com/JeroenDeDauw/ParamProcessor) for more information about the supported declarations.

<pre>
	/**
	 * @see ResultPrinter::getParamDefinitions
	 *
	 * {@inheritDoc}
	 */
	public function getParamDefinitions( array $definitions ) {

		// You should always get the params added by the parent class,
		// using the parent.
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions[] = [
			'name' => 'separator',
			'message' => 'smw-paramdesc-separator',
			'default' => '',
		];

		return $definitions;
	}
</pre>

### Building an output

This is an example from the [`DsvResultPrinter.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/src/Query/ResultPrinters/DsvResultPrinter.php).

<pre>

	/**
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $queryResult, $outputMode ) {

		if ( $outputMode !== SMW_OUTPUT_FILE ) {
			return $this->getDsvLink( $queryResult, $outputMode );
		}

		return $this->buildContents( $queryResult );
	}

	private function buildContents( QueryResult $queryResult ) {
		$lines = [];

		// Do not allow backspaces as delimiter, as they'll break stuff.
		if ( trim( $this->params['separator'] ) != '\\' ) {
			$this->params['separator'] = trim( $this->params['separator'] );
		}

		/**
		 * @var ResultPrinter::mShowHeaders
		 */
		$showHeaders = $this->mShowHeaders;

		if ( $showHeaders ) {
			$headerItems = [];

			foreach ( $queryResult->getPrintRequests() as $printRequest ) {
				$headerItems[] = $printRequest->getLabel();
			}

			$lines[] = $this->getDSVLine( $headerItems );
		}

		// Loop over the result objects (pages).
		while ( $row = $queryResult->getNext() ) {
			$rowItems = [];

			/**
			 * Loop over their fields (properties).
			 * @var SMWResultArray $field
			 */
			foreach ( $row as $field ) {
				$itemSegments = [];

				// Loop over all values for the property.
				while ( ( $object = $field->getNextDataValue() ) !== false ) {
					$itemSegments[] = Sanitizer::decodeCharReferences( $object->getWikiValue() );
				}

				// Join all values into a single string, separating them with comma's.
				$rowItems[] = implode( ',', $itemSegments );
			}

			$lines[] = $this->getDSVLine( $rowItems );
		}

		return implode( "\n", $lines );
	}
</pre>

Putting in comments and using type hinting can make the code a lot clearer. Also make sure you split up the functionality into multiple methods when it makes sense.

In general, do not create methods longer then 70 lines, unless they are very simple. Avoid putting a lot of code in a loop, or worse yet, nested loop. In following example there is only a single line in the inner loop, if it where say 20, it'd be better to put this into a separate method (ie such as was done with the things that are in with the `DsvResultPrinter::getDSVLine` method.

### Using JavaScript

If you have data that requires JavaScript (e.g plotting a chart etc.) do not create a string with JavaScript in which you insert PHP variables, and create an inline JS output. Inline JS with logic is prohibited, instead just construct a data object (i.e. JSON) which you then is interpret with JS in a separate file. This way the page will load faster and the code will cleaner.

A simple way to get all your data from PHP to JS is to create one big PHP object (arrays and associative arrays) that holds the values and turn it into JSON using `json_encode`. This function takes care of all escaping for you after which you need to use the `SMWOutputs` class to ensure your code works both in articles (when the result printers is used for inline ask queries), and on special pages, such as `Special:Ask` and `Special:Browse`. The method needed for adding your JS is SMWOutputs::requireHeadItem, which takes an ID and your actual JS.

<pre>
	$requireHeadItem = [ $id => json_encode( $data ) ];

	\SMWOutputs::requireHeadItem(
		// Unique id
		$id,
		\Skin::makeVariablesScript( $requireHeadItem )
	);
</pre>

Predefined modules and styles can be added by overriding the `ResultPrinter::getResources` method to return something like:

<pre>
	/**
	 * @see ResultPrinter::getResources
	 */
	protected function getResources() {
		return [
			'modules' => [
				'smw.foo'
			],
			'styles' => [
				'smw.foo.styles'
			]
		];
	}
</pre>

It provides a convenient way to add resources such as JavaScript and CSS. This method is a stub in the `ResultPrinter` class which can be overridden, but is not required.

## Adding tests

Most `ResultPrinter` have integration tests to verify that the expected output is actually generated when different types of queries (e.g. with or without headers, with or without a mainlabel etc.) are requested.

## Registering the format

To register new format you need to add your format name to [`$smwgResultFormats`](https://www.semantic-mediawiki.org/w/index.php/Help:$smwgResultFormats) setting:

<pre>
$GLOBALS['smwgResultFormats']['foo-format'] = \SMW\Query\ResultPrinters\FooResultPrinter::class;
</pre>

For SRF use the [`$srfgFormats`](https://github.com/SemanticMediaWiki/SemanticResultFormats/blob/master/DefaultSettings.php) setting:

<pre>
$GLOBALS['srfgFormats'][] = 'my-format';
</pre>

## See also

- [`boilerplate.resultprinter`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/code-snippets/boilerplate.resultprinter.md) starting point for writing a result printer
- [`boilerplate.fileexportprinter`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/code-snippets/boilerplate.fileexportprinter.md) starting point for writing a file result printer
- [`boilerplate.resultprinter.example`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/code-snippets/boilerplate.resultprinter.example.md) a complete example
