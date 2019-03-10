Semantic MedaiWiki and many of its extensions stick to a strict naming conventions and code styles based on the [MediaWiki coding conventions](http://www.mediawiki.org/wiki/Manual:Coding_conventions). The guidelines can often be adopted to other extensions by just changing the prefix <tt>SMW</tt>.

## Files and folders

- The project follows the `PSR-4` guideline in naming and using namespaces for classes where `SMW` is the top-vendor identifier,
- Classes in the `src` folder follow the `PSR-4` layout
- Due to legacy reasons classes in the `includes` folder have no or very rudimentary test coverage and do not follow `PSR-4`. Once a class meets certain criteria (one of which is sufficient test coverage among others) it is expected to be moved into the `src` folder with the objective to remove `includes` in future

## Encoding

- All files need to be stored as `UTF8` (this is absolutely crucial)
- All line endings must be UNIX-style

## PHP

### Class annotations

The `@private` annotation for a class indicates a solely restricted use within the SMW-core code base and even though a class might provide public access methods, it SHOULD NOT be expected that the class itself or its methods will be available during or after a specific release cycle.

### Naming conventions

In general, all names are written using the [CamelCase](https://en.wikipedia.org/wiki/Camel_case) style, although methodNames and variableNames typically start with lower case letters. Private methods are not required to follow the convention.

- `Classes` use a [namespace](http://php.net/manual/en/language.namespaces.php) that starts with with "SMW". Class definitions that are encapsulated in methods do not have a prefix. Another exception are classes for '''Specials''' which should be named after the special, as is common in MediaWiki.
- `Functions` that are accessible globally require the "smwf" prefix
- `Variables` are prefixed with "smwg" if declared globally. Variables in a class do not have a special prefix. Local variables in functions typically use the camelCase style or `_` if any separation is needed.
- `Constants` are written ALL_CAPS with underscores as internal separator, and are to be prefixed with "SMW_" when used globally (e.g. in "define('SMW_FOO', 1)"), class constants are free from this convention.

### Code layout and indenting

In general, code layout is guided by the [MediaWiki coding conventions](http://www.mediawiki.org/wiki/Manual:Coding_conventions).  Please be sure to read this document.

- Do not use a closing "?>" tag in your source files. It is a source for errors that is not needed in files.
- Document all your code (see source documentation for details)
- Avoid single lines of code becoming too long.
- '''Indenting''' of program blocks is done with tabulators, not with spaces. All program blocks are indented.
- All indented program blocks should be enclosed with { and }, even if they have one line only.
- Using in-line conditionals for value computations is fine ("condition?thenvalue:elsevalue").
- Spaces around "=" (variable assignment) and all operators, including "." (string concatenation), are recommended.
- In conditionals, conjunctions and disjunctions should be written as ''&&'' and ''||'', respectively, not as ''and'' and ''or''.
- Value constants like '''true''', '''false''', and '''null''' are always written lower case.
- Class-members should be declared '''private''' and only use '''protected''' if sharing with sub-classes is expected. The use of '''public''' is required to indicate a publicly available method.
- Use the keyword '''static''' where only appropriate (static methods or properties are out of an object context, so you cannot use `$this` inside of a static method)
- When you finish some task, take some time to '''remove unused''' debug-statements, functions, and local variables from your code!

## JavaScript

### Naming conventions

In general, all names are written CamelCase, although methodNames and variableNames typically start with lower case letters.

- '''Variables''' mostly don't adhere to any naming conventions, but global variables should have the prefix "smw".
- JavaScript modules are registered with the [ResourceLoader](https://www.mediawiki.org/wiki/ResourceLoader) using [`Resources.php`](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/res/Resources.php)

### Code layout and indenting

- ''No general code layout for SMW's JavaScript has been proposed or implemented yet.''
- [JSHint](http://www.jshint.com/) can help detect errors and potential problems in JavaScript code

## Source documentation

- Every class and function ''must'' have a short documentation, enclosed in "/** ... */". These blocks use the [doxygen](https://en.wikipedia.org/wiki/Doxygen) notation.
- Use `@since` to indicate the version in which the function or field was added. Also do this for hooks.
- Use [type hinting](http://php.net/manual/en/language.oop5.typehinting.php) where possible. It is important to do this in new code that can be inherited, as deriving classes won't be able to use type hinting when this is not done in the base class.
- Use `@todo` and `@bug` in doxygen comments to keep track of to-dos and bugs directly within the code.
- Complex code and hacks should be documented with C-style line-comments ("// ...").
- User documentation should be documented with Shell-style line-comments ("# ...").
- Implementations that affect users and existing features ''must'' be documented in the [user manual](https://www.semantic-mediawiki.org/wiki/Help:User_manual) before release.
- Changes that are relevant to users, administrators, or third-party developers ''must'' be mentioned in the file [RELEASE-NOTES](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/RELEASE-NOTES.md) where a short note is enough (incl. the PR).
