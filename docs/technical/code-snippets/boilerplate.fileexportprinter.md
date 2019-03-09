<pre>
/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author ...
 */
class FooFileExportPrinter extends FileExportPrinter {

	/**
	 * @see ResultPrinter::getName
	 *
	 * {@inheritDoc}
	 */
	public function getName() {
		return '';
	}

	/**
	 * @see FileExportPrinter::getMimeType
	 *
	 * {@inheritDoc}
	 */
	public function getMimeType( QueryResult $queryResult ) {
		return '';
	}

	/**
	 * @see FileExportPrinter::getFileName
	 *
	 * {@inheritDoc}
	 */
	public function getFileName( QueryResult $queryResult ) {
		return '';
	}

	/**
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
	 * @see ResultPrinter::getResultText
	 *
	 * {@inheritDoc}
	 */
	protected function getResultText( QueryResult $queryResult, $outputMode ) {
		return '';
	}
}
</pre>
