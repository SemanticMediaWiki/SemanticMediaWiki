<?php
$config = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$config['suppress_issue_types'] = array_merge(
	$config['suppress_issue_types'],
	[
		// Suppress issue types that currently exist in the codebase.
		// This means that Phan initially won't do much, but it allows for
		// checks to be incrementally fixed and enabled without massive changes.
		'PhanImpossibleCondition',
		'PhanImpossibleTypeComparison',
		'PhanParamSignatureMismatch',
		'PhanPluginInvalidPregRegex',
		'PhanPluginLoopVariableReuse',
		'PhanPluginRedundantAssignment',
		'PhanPossiblyUndeclaredVariable',
		'PhanStaticCallToNonStatic',
		'PhanTypeArraySuspicious',
		'PhanTypeArraySuspiciousNullable',
		'PhanTypeInvalidLeftOperandOfAdd',
		'PhanTypeInvalidLeftOperandOfNumericOp',
		'PhanTypeMismatchArgument',
		'PhanTypeMismatchArgumentInternal',
		'PhanTypeMismatchArgumentNullable',
		'PhanTypeMismatchArgumentNullableInternal',
		'PhanTypeMismatchArgumentProbablyReal',
		'PhanTypeMismatchDimAssignment',
		'PhanTypeMismatchDimFetchNullable',
		'PhanTypeMissingReturn',
		'PhanTypePossiblyInvalidDimOffset',
		'PhanUndeclaredClassInstanceof',
		'PhanUndeclaredClassMethod',
		'PhanUndeclaredConstant',
		'PhanUndeclaredExtendedClass',
		'PhanUndeclaredMethod',
		'PhanUndeclaredStaticMethod',
		'PhanUndeclaredTypeThrowsType',
		'PhanUndeclaredVariable',
		'PhanUndeclaredVariableAssignOp',
		'PhanUndeclaredVariableDim',
		'SecurityCheck-DoubleEscaped',
		'SecurityCheck-SQLInjection',
		'SecurityCheck-XSS',
		// Required php8+
		'PhanUnusedVariableCaughtException',
	]
);

// Include only direct production dependencies in vendor/
// Omit dev dependencies and most indirect dependencies

$composerJson = json_decode(
	file_get_contents( __DIR__ . '/../composer.json' ),
	true
);

$directDeps = [];
foreach ( $composerJson['require'] as $dep => $version ) {
	$parts = explode( '/', $dep );
	if ( count( $parts ) === 2 ) {
		$directDeps[] = $dep;
	}
}

foreach ( [ ...$directDeps ] as $dep ) {
	$config['directory_list'][] = "vendor/$dep";
}

$config['exclude_analysis_directory_list'] = [
	'vendor/',
	'.phan/',
	'tests/phpunit/',
];

return $config;
