There are different ways to make a contribution to [Semantic MediaWiki][smw] (SMW) while a few guidelines are necessary to keep the workflow and review process most efficient.

### Report bugs

* You may help us by reporting bugs and feature requests via the [issue tracker][smw-issues]. See the help page on [reporting bugs (environment, reproducing)][smw-bugs1] as well as on [identifying bugs (stack-trace)][smw-bugs2] for information on how to do this best. Please remember to always provide information about your environment as well as a stack-trace.
* You may help us to do [pre-release testing][smw-testing] by joining the [team of testers][smw-testers] on GitHub.

### Improve documentation

* You may help us by providing software translations via [translatewiki.net][twn]. See their [progress-statistics][twn-smw] to find out if there is still work to do for your language.
* You may help us by creating, updating or amending the documentation of the software on [Semantic-MediaWiki.org][smw].

### Provide patches

You may help us by providing patches or additional features via a pull request but in order to swiftly co-ordinate your code contribution, the following should suffice:

* Please ensure that pull requests are based on the current master. See also the [developer documentation overview][smw-ddo] for further information.
* Code should be easily readable (see [NPath complexity][smw-npath], `if else` nesting etc.) and if necessary be put into separate components (or classes)
* Newly added features should not alter existing tests but instead provide additional test coverage to verify the expected new behaviour. For a description on how to write and run PHPUnit test, please consult the [manual][mw-testing].

Thank you for contributing to Semantic MediaWiki!

[smw]: https://github.com/SemanticMediaWiki/SemanticMediaWiki
[smw-issues]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues
[smw-bugs1]: https://www.semantic-mediawiki.org/wiki/Help:Reporting_bugs
[smw-bugs2]: https://www.semantic-mediawiki.org/wiki/Help:Identifying_bugs
[smw-testing]: https://www.semantic-mediawiki.org/wiki/Help:Reporting_bugs#Pre-release_testing
[smw-testers]: https://github.com/orgs/SemanticMediaWiki/teams/testers
[twn]: https://translatewiki.net/
[twn-smw]: https://translatewiki.net/w/i.php?title=Special%3AMessageGroupStats&x=D&group=ext-semanticmediawiki&suppressempty=1
[smw-ddo]: https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/technical/README.md
[mw-testing]: https://www.mediawiki.org/wiki/Manual:PHP_unit_testing
[smw-npath]: https://www.semantic-mediawiki.org/wiki/Code_coverage#NPath_complexity
