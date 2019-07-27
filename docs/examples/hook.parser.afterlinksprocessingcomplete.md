## Register a # hash tag parser

The example will use the `SMW::Parser::AfterLinksProcessingComplete` to process hash tags (e.g. #foo #foo_bar etc.) and add value annotations for the identified tag.

```php
use SMW\Parser\Annotationprocessor;

class HashTagParser {

	private $subject;
	private $property;
	private $annotationprocessor;
	private $showErrors = false;

	public function __construct( Annotationprocessor $annotationprocessor ) {
		$this->annotationprocessor = $annotationprocessor;
	}

	public function setShowErrors( $showErrors ) {
		$this->showErrors = $showErrors;
	}

	public function parse( $text, $property ) {

		$semanticData = $this->annotationprocessor->getSemanticData();

		$this->property = $property;
		$this->subject = $semanticData->getSubject();

		// Twitter regex (include underscore and not start with a number):
		// (?<=^|\P{L})(#\b\p{L}[\p{L}\d_]+)

		// Allow to start with a number
		// (?<!&|\S)#(\w+)

		return preg_replace_callback( '/(?<!&|\S)#(\w+)/u', [ $this, 'process' ], $text );
	}

	public function process( array $matches ) {

		$dataValue = $this->annotationprocessor->newDataValueByText(
			$this->property,
			$matches[1],
			$matches[0],
			$this->subject
		);

		if ( $this->annotationprocessor->canAnnotate() ) {
			$this->annotationprocessor->getSemanticData()->addDataValue( $dataValue );
		}

		// If necessary add an error text
		if ( ( $this->showErrors && !$dataValue->isValid() ) ) {
			// Encode `:` to avoid a comment block and instead of the nowiki tag
			// use &#58; as placeholder
			$result = str_replace( ':', '&#58;', $result ) . $dataValue->getErrorText();
		} else {
			$result = $dataValue->getShortWikitext( true );
		}

		return $result;
	}

}
```

```php
\Hooks::register( 'SMW::Parser::AfterLinksProcessingComplete', function( &$text, $annotationprocessor ) {

	$hashTagParser = new HashTagParser(
		$annotationprocessor
	);

	$text = $hashTagParser->parse( $text, 'Has keyword' );

	return true;
} );
```