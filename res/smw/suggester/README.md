
## Register an input context

Adding a input context can be done fairly easy using something like:

<pre>
// Ensures that the module is loaded before trying to access an
// instance.
mw.loader.using( [ 'ext.smw.suggester' ], function() {

	var context = $( '#someElement > input' );

	if ( context.length ) {

		var entitySuggester = smw.Factory.newEntitySuggester(
			context
		);

		// Register default tokens
		entitySuggester.registerDefaultTokenList(
			[
				'property',
				'concept',
				'category'
			]
		);
	};
} );
</pre>

## Register an additional token

It may be desired to define additional tokens that active a suggestion request.

<pre>
mw.loader.using( [ 'ext.smw.suggester' ], function() {

	var context = $( '#someElement > input' );

	if ( context.length ) {

		var entitySuggester = smw.Factory.newEntitySuggester(
			context
		);

		// Register default tokens
		entitySuggester.registerDefaultTokenList(
			[
				'property',
				'concept',
				'category'
			]
		);

		entitySuggester.registerTokenDefinition(
			'property',
			{
				token: '?p:',
				beforeInsert: function( token, value ) {
					return value.replace( 'p:', '' );
				}
			}
		);
	};
} );
</pre>