# JSONScript

One best practice approach in Semantic MediaWiki is to write integration tests as
pseudo `JSONScript` to allow non-developers to review and understand the setup and
requirements of its test scenarios.

The `JSON` format was introduced as abstraction layer to lower the barrier of
understanding of what is being tested by using the wikitext markup to help design
test cases quicker without the need to learn how `PHPUnit` or internal `MediaWiki`
objects work.

* [List of available test cases](#testcases)
* [Designing an integration test](#designing-an-integration-test)
* [Technical notes](#technical-notes)

<!-- Begin of generated contents by readmeContentsBuilder.php -->

## TestCases

Contains 202 files with a total of 877 tests:

### F
* [f-0001.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0001.json) Test `format=debug` output
* [f-0101.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0101.json) Test `format=template` output using unnamed arguments (#885)
* [f-0102.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0102.json) Test `format=template` output + unicode characters (#988, skip postgres)
* [f-0103.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0103.json) Test `format=template` with self reference (#988, guard against template self-reference in ask/show query)
* [f-0104.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0104.json) Test `format=list, ul, ol, template` (#2022,`wgContLang=en`, `wgLang=en`)
* [f-0105.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0105.json) Test `format=list, ul, ol` on `_qty` property (`wgContLang=en`, `SMW_DV_NUMV_USPACE`)
* [f-0201.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0201.json) Test `format=table` on boolean table output formatting (#896, #1464)
* [f-0202.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0202.json) Test `format=table` with sep cell formatting, #495 (`wgContLang=en`,`wgLang=en`)
* [f-0203.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0203.json) Test `format=table` to sort by category (#1286)
* [f-0204.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0204.json) Test `format=table` on `_qty` for different positional unit preference (#1329, en)
* [f-0205.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0205.json) Test `format=table` on `|+align=`/`|+limit`/`|+order` extra printout parameters (T18571, en)
* [f-0206.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0206.json) Test `format=table` to display extra property description `_PDESC` (en)
* [f-0207.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0207.json) Test `format=table` on formatted indent when using */#/: (en)
* [f-0208.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0208.json) Test `format=table` with `limit=0` (further result links) for user/predefined properties, `mainlabel=-`, `#show` (`wgContLang=en`, `wgLang=es`)
* [f-0209.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0209.json) Test `format=table` on `_tem`/ `_num` with `LOCAL@...` output (#1591, `wgContLang=es`, `wgLang=en`)
* [f-0210.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0210.json) Test `format=table` on `_qty` for unit labels with spaces (#1718, `wgContLang=en`, `SMW_DV_NUMV_USPACE`)
* [f-0301.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0301.json) Test `format=category` with template usage (#699, en, skip postgres)
* [f-0302.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0302.json) Test `format=category` and defaultsort (#699, en)
* [f-0303.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0303.json) Test `format=category` sort output using a template and DEFAULTSORT (#1459, en)
* [f-0401.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0401.json) Test `format=list` output
* [f-0801.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0801.json) Test `format=embedded` output (skip 1.19)
* [f-0802.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0802.json) Test `format=template` [[SMW::on/off]] regression using `named args=yes` (#1453, skip-on 1.19)
* [f-0803.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/f-0803.json) Test `format=template` with `sep`/`named args`/`template arguments` (#972, #2022)

### P
* [p-0101.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0101.json) Test in-text annotation for use of restricted properties (#914, `wgContLang=en`, `wgLang=en`)
* [p-0102.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0102.json) Test in-text annotation on properties with invalid names/charaters (#1567, #1638, #1727 `wgContLang=en`)
* [p-0106.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0106.json) Test #info parser output (#1019, `wgContLang=en`, `wgLang=en`)
* [p-0107.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0107.json) Test #smwdoc parser output (#1019, en)
* [p-0108.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0108.json) Test `#info`, `#ask` template output (#2347, `wgContLang=en`, `wgLang=en`)
* [p-0110.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0110.json) Test tooltip with error output on `_PVUC` (`smwgDVFeatures`, `wgContLang=en`, `wgLang=en`)
* [p-0202.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0202.json) Test #set parser to use template for output (#1146, en)
* [p-0203.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0203.json) Test #set parser in combination with #subobject and template output (#1067, regression check)
* [p-0204.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0204.json) Test #set parser to produce error output (#870, en, verify that #set calls do not affect each other with previous errors)
* [p-0205.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0205.json) Test #set/#ask recursive annotation support (#711, #1055, recursive annotation using import-annotation=true via template)
* [p-0206.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0206.json) Test #show parser on inverse printrequest (#1222, #1223)
* [p-0207.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0207.json) Test that undeclared properties with references remain after a `rebuildData` run (#1216, en)
* [p-0208.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0208.json) Test `#set` for various `_num` values without explicit precision (3 digit implicit), with/without leading zero, different printouts, negative numbers (#753, en, `smwgMaxNonExpNumber`)
* [p-0209.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0209.json) Test `#set` for various `_qty` values without explicit precision (3 digit implicit), with/without leading zero, and different printouts (#753, en, `smwgMaxNonExpNumber`)
* [p-0210.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0210.json) Test `#set_recurring_event` (`wgContLang=en`, `wgLang=en`)
* [p-0211.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0211.json) Test `#set`/`#subobject` to import annotation via `@json` syntax (`wgContLang=en`, `wgLang=en`)
* [p-0212.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0212.json) Test `@@@` in-text annotation syntax (#1855, #1875 `wgContLang=en`, `wgLang=en`)
* [p-0301.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0301.json) Test #subobject category annotation (#1172)
* [p-0302.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0302.json) Test #subobject parser to use invalid assignments and create `_ERRC` (#1299, en)
* [p-0303.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0303.json) Test `#subobject` and `#set` parser on values with spaces (`wgContLang=en`, `wgLang=en`)
* [p-0401.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0401.json) Test annotations with disabled capital links (#673, `wgCapitalLinks=false`)
* [p-0402.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0402.json) Test in-text parsing for double colon annotation such as `::::` or `:::` (#1066, #1075, en)
* [p-0403.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0403.json) Test in-text annotations being disabled for when Factbox contains extra `[[ ... ]]` (#1126)
* [p-0404.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0404.json) Test in-text annonation on different category colon identifier
* [p-0405.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0405.json) Test in-text annotation via template and manual redirect (#895)
* [p-0406.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0406.json) Test in-text annotation for unrestricted template parse using `import-annotation=true` (#1055)
* [p-0407.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0407.json) Test in-text annotation for a redirect that is pointing to a deleted target (#1105)
* [p-0408.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0408.json) Test in-text annotation for multiple property assignment using non-strict parser mode (#1252, en)
* [p-0409.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0409.json) Test in-text annotation for `_rec`/`_mlt_rec` (+ subobject) for when record type points to another record type (`wgContLang=en`, `wgLang=en`)
* [p-0410.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0410.json) Test in-text annotation on `_num`/`_tem`/`_qty` type with denoted precision (`_PREC`) and/or `-p<num>` printout precision marker (#1335, en)
* [p-0411.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0411.json) Test in-text annotation (and #subobject) using a monolingual property (#1344, en)
* [p-0412.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0412.json) Test in-text annotation for `_boo` datatype (`wgContLang=ja`, `wgLang=ja`)
* [p-0413.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0413.json) Test in-text annotation for different `_dat` input/output (en, skip virtuoso, `smwgDVFeatures`)
* [p-0414.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0414.json) Test in-text annotation/free format for `_dat` datatype (#1389, #1401, en, `smwgDVFeatures`)
* [p-0415.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0415.json) Test in-text annotation on `_tem` with display unit preference (en)
* [p-0416.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0416.json) Test in-text annotation with DISPLAYTITLE (#1410, #1611, `wgRestrictDisplayTitle`, `wgContLang=en`, `wgLang=en`)
* [p-0417.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0417.json) Test in-text annotation for `Allows pattern` to match regular expressions (en)
* [p-0418.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0418.json) Test in-text annotation using `_SERV` as provide service links (en)
* [p-0419.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0419.json) Test in-text annotation for `_PVUC` to validate uniqueness (`smwgDVFeatures`)
* [p-0420.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0420.json) Test in-text annotation for `_dat` using JL/GR annotated values (en, `smwgDVFeatures`)
* [p-0421.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0421.json) Test in-text annotation with combined constraint validation (`smwgDVFeatures`)
* [p-0422.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0422.json) Test in-text annotation `_dat` on partial dates (#2076, `wgContLang=en`, `wgLang=en`)
* [p-0423.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0423.json) Test in-text annotation / `#ask` (#MEDIAWIKI, #LOCL) output for `_dat` datatype (#1545, `wgContLang=en`, `wgLang=ja`)
* [p-0424.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0424.json) Test in-text annotation for `_boo` datatype using `LOCL` (`wgContLang=en`, `wgLang=fr`, skip-on 1.25.6)
* [p-0425.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0425.json) Test in-text annotation on `_tem`/ `_num` with different page content language (#1591, `wgContLang=es`, `wgLang=en`)
* [p-0426.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0426.json) Test in-text annotation for `_num` on big/small numbers/scientific notation (`wgContLang=fr`, `wgLang=en`)
* [p-0427.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0427.json) Test in-text annotation with DISPLAYTITLE / `foaf` to check on upper vs. lower case (`wgRestrictDisplayTitle`, `wgContLang=en`, `wgLang=en`)
* [p-0428.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0428.json) Test `_TYPE` annotations on different content language (`wgContLang=fr`, `wgLang=en`)
* [p-0429.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0429.json) Test in-text `_dat` annotation with time offset, time zone, am/pm (`wgContLang=en`, `wgLang=en`)
* [p-0430.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0430.json) Test in-text annotation for `_eid` type (`wgContLang=en`, `wgLang=en`)
* [p-0431.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0431.json) Test in-text annotation `_rec` and `|+index` (`wgContLang=en`, `wgLang=en`)
* [p-0432.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0432.json) Test in-text annotation for `_ref_rec` type (#1808, `wgContLang=en`, `wgLang=en`)
* [p-0433.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0433.json) Test in-text annotation `::` with left pipe (#1747, `wgContLang=en`, `smwgLinksInValues=false`)
* [p-0434.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0434.json) Test printrequest property chaining `|?Foo.Bar` (#1824, `wgContLang=en`, `wgLang=en`)
* [p-0435.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0435.json) Test in-text annotation using `_txt` type with 255+ char, `#ask` to produce reduced length (#1878, `wgContLang=en`, `wgLang=en`)
* [p-0436.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0436.json) Test in-text annotation with `_PPLB` [preferred property label] (#1879, `wgContLang=en`, `wgLang=en`)
* [p-0437.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0437.json) Test in-text annotation with preferred property label/`_PPLB` (#1879, `wgContLang=en`, `wgLang=ja`)
* [p-0438.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0438.json) Test in-text annotation with preferred property label/DISPLAYTITLE on user/predefined properties (`wgContLang=es`, `wgLang=de`, `wgRestrictDisplayTitle=false`)
* [p-0439.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0439.json) Test in-text annotation using '_txt'/'_wpg' type / UTF encoding (`wgContLang=en`, `wgLang=en`)
* [p-0440.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0440.json) Test in-text annotation `_mlt_rec` (Monolingual text) with `|+lang`/`|+order` parameter (`wgContLang=en`, `wgLang=en`)
* [p-0441.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0441.json) Test in-text `_txt` 00 string/loose comparison (#2061)
* [p-0442.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0442.json) Test in-text `#REDIRECT` to verify target subobject isn't removed (#, `wgContLang=en`, `wgLang=en`)
* [p-0443.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0443.json) Test conditions and strict constraint validations for uniqueness `_PVUC` (#1463, `wgContLang=en`, `wgLang=en`, `smwgDVFeatures`)
* [p-0444.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0444.json) Test in-text annotation LinksInValue `SMW_LINV_OBFU` (#2153, `wgContLang=en`, `smwgLinksInValues=SMW_LINV_OBFU`)
* [p-0445.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0445.json) Test in-text annotation for `_ref_rec` type with errors (#..., `wgContLang=en`)
* [p-0446.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0446.json) Test in-text annotation `_uri`/`_ema`/`_tel` with spaces/underscore (`wgContLang=en`)
* [p-0447.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0447.json) Test in-text annotation with IRI export (#2188, `smwgExportResourcesAsIri=true`, `wgContLang=ru`, `wgLang=en`)
* [p-0448.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0448.json) Test in-text legacy `:=` annotation style (#2153, `wgContLang=en`, `smwgLinksInValues=false`)
* [p-0449.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0449.json) Test in-text legacy `:=` and `::` annotation style with enabled LinksInValue (#2153, `wgContLang=en`, `smwgLinksInValues=SMW_LINV_OBFU`)
* [p-0451.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0451.json) Test in-text `_dat` datatype, time zone, and JD output (#2454, `wgContLang=en`, `wgLang=en`, `smwgDVFeatures=SMW_DV_TIMEV_CM`)
* [p-0501.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0501.json) Test `#concept` on predefined property (`wgContLang=en`, `wgLang=es`)
* [p-0502.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0502.json) Test in-text annotation allows value list (#2295, `wgContLang=en`, `wgLang=en`)
* [p-0701.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0701.json) Test to create inverted annotation using a #ask/template combination (#711, `import-annotation=true`)
* [p-0702.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0702.json) Test #ask with `format=table` on inverse property/printrquest (#1270, en)
* [p-0703.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0703.json) Test `#ask` on `format=table` using different printrequest label output (#1270, `wgContLang=en`, `wgLang=en`)
* [p-0704.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0704.json) Test `#ask` sanitization of printrequest labels to avoid XSS injection (`wgContLang=en`, `wgLang=en`)
* [p-0705.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0705.json) Test `#ask`/ NS_FILE option (`wgContLang=en`, `wgLang=en`, `wgEnableUploads`, `wgFileExtensions`, 'wgDefaultUserOptions')
* [p-0706.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0706.json) Test `#ask` on `format=template` with message parse (`wgContLang=en`, `wgLang=en`)
* [p-0901.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0901.json) Test #ask on moved redirected subject (#1086)
* [p-0902.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0902.json) Test #ask on failed queries to produce a `_ERRC` (#1297, en)
* [p-0903.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0903.json) Test #ask on redirected printrequest (#1290, en)
* [p-0904.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0904.json) Test #ask with subject redirected to different NS (en)
* [p-0905.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0905.json) Test `#ask` query-in-query construct (`_sobj`/`_dat`/`_num`) (`wgContLang=en`, `wgLang=en`)
* [p-0906.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0906.json) Test `#ask` on category/property hierarchy with circular reference (#1713, `wgContLang=en`, `wgLang=en`, 'smwgEnabledQueryDependencyLinksStore', skip virtuoso)
* [p-0907.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0907.json) Test the QueryResult cache feature (#1251, `wgContLang=en`, `wgLang=en`, `smwgQueryResultCacheType=true`)
* [p-0908.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0908.json) Test the QueryResult cache feature with different `|+lang`/`|+order` prinrequest parameters (#1251, `wgContLang=en`, `wgLang=en`, `smwgQueryResultCacheType=true`)
* [p-0909.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0909.json) Test the description optimization (`wgContLang=en`, `wgLang=en`, `smwgQueryResultCacheType=true`, `smwgQFilterDuplicates=true`, `smwgQueryProfiler`)
* [p-0910.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0910.json) Test `#ask` to highlight (`#-hl`) search token in result set (#..., `wgContLang=en`, `wgLang=en`)
* [p-0911.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0911.json) Test the `_ASK` profile (#2270, `smwgQueryProfiler`, `smwgQueryResultCacheType`)
* [p-0912.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0912.json) Test `#ask` with (`#-ia`) formatter using `#set` (#..., `wgContLang=en`, `wgLang=en`)
* [p-0913.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0913.json) Test `#ask` with (`#-ia`) formatter using `smwgLinksInValues` (#..., `wgContLang=en`, `wgLang=en`, `smwgLinksInValues=SMW_LINV_OBFU`)
* [p-0914.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-0914.json) Test the description optimization on `_ref_rec` type with property chain query/sort (`wgContLang=en`, `wgLang=en`, `smwgQueryResultCacheType=true`, `smwgQFilterDuplicates=true`, `smwgQueryProfiler`)
* [p-1000.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-1000.json) Test property page with redirect(synonym)/displayTitle (`wgContLang=en`, `wgLang=en`, `wgAllowDisplayTitle`)
* [p-1001.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/p-1001.json) Test property page with parameters (#2479, `wgContLang=en`, `wgLang=en`)

### Q
* [q-0101.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0101.json) Test `_txt` query for simple assignments, NS_HELP, and special chars
* [q-0102.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0102.json) Test `_txt` for `~*` regex queries to validate correct escape pattern as applied in the `QueryEngine`
* [q-0103.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0103.json) Test `_txt` for `~*` regex query with the condition to include the `\` escape character (skip sqlite, postgres)
* [q-0104.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0104.json) Test `_txt`/`~` with enabled full-text search support (only enabled for MySQL, SQLite)
* [q-0105.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0105.json) Test `_wpg`/`~` with enabled full-text search support (only enabled for MySQL, SQLite, `SMW_FT_WIKIPAGE`)
* [q-0201.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0201.json) Test `_CONC` queries (skip postgres, skip virtuoso)
* [q-0202.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0202.json) Test `_CONC` for guarding against circular/self-reference which otherwise would fail with 'Maximum function nesting level ... reached, aborting' (#945, skip virtuoso)
* [q-0203.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0203.json) Test `_CONC` to use `CONCEPT_CACHE_ALL` (#1050, skip postgres + all SPARQL repository)
* [q-0204.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0204.json) Test `_CONC` on predefined inverse query and subobject inverse query (#1096, skip virtuoso)
* [q-0301.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0301.json) Test `_IMPO` queries for imported foaf vocabulary (#891, en)
* [q-0401.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0401.json) Test `_SUBP` on a simple 'family' subproperty hierarchy example query (#1003, skip virtuoso)
* [q-0402.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0402.json) Test `_SUBP` to map DC imported vocabulary with MARC 21 bibliographic terms (#1003, http://www.loc.gov/marc/bibliographic/bd20x24x.html)
* [q-0501.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0501.json) Test `_qty` queries for custom unit (km²/°C) property value assignments
* [q-0502.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0502.json) Test `_qty` range queries using non strict comparators (`smwStrictComparators=false`)
* [q-0503.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0503.json) Test `_qty` on positional unit preference in query condition (#1329, `smwStrictComparators=false`)
* [q-0601.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0601.json) Test `_wpg` for property chain query queries
* [q-0602.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0602.json) Test `_wpg` sort query with #subobject annotated @sortkey content
* [q-0603.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0603.json) Test `_wpg` queries for various conditions using #set annotated content
* [q-0604.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0604.json) Test `_wpg` queries to resolve property/values redirects (#467, skip virtuoso)
* [q-0605.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0605.json) Test `_wpg` regex search (`!~/~*/~?`) queries (#679)
* [q-0606.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0606.json) Test `_wpg`/`_num`/`_txt` using subqueries (#466, #627, #625)
* [q-0607.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0607.json) Test `_wpg`/`_dat`/`_num`/`_txt` subquery example
* [q-0608.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0608.json) Test `_wpg` for single value approximate (`~/!~`) queries (#1246)
* [q-0609.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0609.json) Test `_wpg` for single value approximate (`~/!~`) queries with conjunctive category hierarchy (#1246, en, skip virtuoso)
* [q-0610.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0610.json) Test `_wpg` range queries (#1291, `smwStrictComparators=false`, skip virtuoso)
* [q-0611.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0611.json) Test `_wpg` namespace any value queries (#1301, en)
* [q-0612.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0612.json) Test `_wpg` object value that contains `=` (equals sign) (#640, #710, #1542, #1645, `wgContLang=en`, `wgLang=en`)
* [q-0613.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0613.json) Test single value (`~/!~`/`<`/`>`) queries on namespaced entity (#1652, `NS_HELP`, `smwStrictComparators=false`, skip-on virtuoso)
* [q-0701.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0701.json) Test `_uri` with some annotation/search pattern (T45264, #679)
* [q-0702.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0702.json) Test `_uri` with additional annotation/search (#1129)
* [q-0703.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0703.json) Test to map `Foaf` property from back-end / using a localized predefined property `A le type@fr` (en)
* [q-0704.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0704.json) Test `_uri` long URL (255+) (#1872)
* [q-0801.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0801.json) Test `_INST` query (#1004, en)
* [q-0802.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0802.json) Test `_INST`/`_SUBC` queries (#1005, en, skip virtuoso)
* [q-0803.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0803.json) Test `_INST`/ Nested category annotation (#1012, en, skip virtuoso) category hierarchy queries
* [q-0901.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0901.json) Test `_wpg`/`_txt` on various disjunction, conjunction queries (#19, #1060, #1056, #1057)
* [q-0902.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0902.json) Test `_txt` to correctly apply parentheses for somehting like (a OR b OR c) AND d (#556)
* [q-0903.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0903.json) Test `_wpg`/`_num`/`_txt` for disjunction OR || (T31866, #1059, en)
* [q-0904.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0904.json) Test `_wpg`/`_txt` disjunction in connection with property hierarchies (#1060, en, skip virtuoso)
* [q-0905.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-0905.json) Test `_wpg`/`_txt` conjunction queries (#1362, #1060)
* [q-1002.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1002.json) Test `_dat` range for non strict comparators (#285, `smwStrictComparators=false`, skip virtuoso)
* [q-1003.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1003.json) Test `_dat` range for strict comparators (#285, `smwStrictComparators=true`, skip virtuoso)
* [q-1004.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1004.json) Test `_dat` range for `~`/`!~` comparators (#1178, `smwStrictComparators=false`, skip virtuoso)
* [q-1101.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1101.json) Test _rec for non strict comparators queries (`smwStrictComparators=false`)
* [q-1102.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1102.json) Test `_rec` queries in combination with `_dat` `~/!~` search pattern (#1178, `smwStrictComparators=false`, skip virtuoso)
* [q-1103.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1103.json) Test `_rec` using some additional search pattern (#1189, en)
* [q-1104.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1104.json) Test `_rec` to find correct target for redirected property (#1244, en)
* [q-1105.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1105.json) Test `_rec` in combination with named subobject (T49472, #1300, en, `smwStrictComparators=false`)
* [q-1106.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1106.json) Test `_rec` with `~/!~` comparators on allowed values (#1207, `smwStrictComparators=false`)
* [q-1107.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1107.json) Test `_rec`/`_mlt_rec`(`_PDESC`) to use property chaining (`wgContLang=en`)
* [q-1108.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/q-1108.json) Test conditions and constraint validations for allowed values `_LIST` and uniqueness `_PVUC` (#1207, `wgContLang=en`, `wgLang=en`)

### R
* [r-0001.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0001.json) Test RDF output for `_txt`/`_wpg`/`_dat` (#881)
* [r-0002.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0002.json) Test RDF output for redirected pages (#882)
* [r-0003.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0003.json) Test RDF output for imported foaf vocabulary (#884, en)
* [r-0004.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0004.json) Test RDF output generation for `_INST`/`_SUBC` pages (#922, en)
* [r-0005.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0005.json) Test RDF wiki-info output (#928, en)
* [r-0006.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0006.json) Test RDF output generation for pages that contain `_rec` annotations (#1285, #1275)
* [r-0007.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0007.json) Test RDF output for imported dc/gna vocabulary, owl:AnnotationProperty, owl:DatatypeProperty, owl:ObjectProperty, Equivalent URI (#795, `wgRestrictDisplayTitle`, en)
* [r-0008.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0008.json) Test RDF output generation on pages that contain incoming error annotations (`wgContLang=en`, `wgLang=es`, syntax=rdf/turtle)
* [r-0009.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0009.json) Test RDF output generation that contain a monolingual text annotations `_PDESC` (`wgContLang=en`, `wgLang=es`, syntax=rdf/turtle)
* [r-0010.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0010.json) Test RDF output on canonical entities (`wgContLang=fr`, `wgLang=es`, syntax=rdf/turtle)
* [r-0011.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0011.json) Test RDF output generation `skos` import/`skos:altLabel` as Monolingual text (`wgContLang=en`, `wgLang=en`)
* [r-0012.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0012.json) Test RDF output generation on SubSemanticData traversal (#2177, `wgContLang=en`, `wgLang=en`)
* [r-0013.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0013.json) Test RDF output generation `_uri`/`_ema`/`_tel` with spaces/underscore (`wgContLang=en`, `wgLang=en`)
* [r-0014.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0014.json) Test RDF output generation on non-latin URI/IRI export (#2188, `smwgExportResourcesAsIri=false`, `wgContLang=ru`, `wgLang=en`)
* [r-0015.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0015.json) Test RDF output generation on non-latin URI/IRI export (#2188, `smwgExportResourcesAsIri=true`, `wgContLang=ru`, `wgLang=en`)
* [r-0016.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0016.json) Test RDF output generation with special characters (#2188, `smwgExportResourcesAsIri=false`, `wgContLang=en`, `wgLang=en`)
* [r-0017.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0017.json) Test RDF output generation with special characters (#2188, `smwgExportResourcesAsIri=true`, `wgContLang=en`, `wgLang=en`)
* [r-0018.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/r-0018.json) Test RDF output generation with special characters (`smwgExportResourcesAsIri=true`, `wgContLang=en`, `wgLang=en`)

### S
* [s-0001.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0001.json) Test output of `Special:Properties` (`wgContLang=en`, skip-on sqlite)
* [s-0002.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0002.json) Test output from `Special:SearchByProperty` for `_num`, `_txt`, `_tel` (#1728, #2009, `wgContLang=en`, `wgLang=en`, skip-on sqlite, postgres)
* [s-0003.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0003.json) Test `Special:Ask` output for `format=rdf`/`format=json`/DISPLAYTITLE (#1453, #1619, `wgRestrictDisplayTitle`, `wgContLang=en`)
* [s-0004.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0004.json) Test `Special:Browse` output for `_dat` (`wgContLang=en`, `wgLang=ja`)
* [s-0005.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0005.json) Test `Special:Browse` output for `_dat`, '_REDI' (`wgContLang=en`, `wgLang=en`, `smwgDVFeatures=SMW_DV_TIMEV_CM | SMW_DV_WPV_DTITLE`, `wgRestrictDisplayTitle=false`)
* [s-0006.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0006.json) Test output of `Special:WantedProperties` (`wgContLang=en`, `wgLang=en`, skip-on sqlite, 1.19)
* [s-0007.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0007.json) Test output of `Special:UnusedProperties` (`wgContLang=en`, `wgLang=en`, skip-on sqlite, 1.19)
* [s-0008.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0008.json) Test `Special:Browse` output for `_dat`, `_boo`, `_sobj`, `_uri` (`wgContLang=en`, `wgLang=es`, skip-on 1.25.6)
* [s-0009.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0009.json) Test output in `Special:Search` for SMWSearch (`wgLanguageCode=en`, `wgContLang=en`, `wgSearchType=SMWSearch`)
* [s-0010.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0010.json) Test output from `Special:SearchByProperty` / `_dat` (#1922, `wgContLang=en`, `wgLang=es`, skip-on sqlite)
* [s-0011.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0011.json) Test `Special:Ask` output `#ask` intro/outro link/template parse (`wgContLang=en`, `wgLang=en`)
* [s-0012.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0012.json) Test `Special:Ask` output `#ask` image/upload (#2009, `wgContLang=en`, `wgLang=en`, `wgEnableUploads`, `wgFileExtensions`)
* [s-0013.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0013.json) Test `Special:Browse` output preferred label (`wgContLang=en`, `wgLang=es`)
* [s-0014.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0014.json) Test `Special:Browse` with special characters `%'"&` (`wgContLang=en`, `wgLang=es` )
* [s-0015.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0015.json) Test `Special:Ask` output for `_txt` with formatted text (#..., `wgContLang=en`, `wgLang=en`)
* [s-0016.json](https://github.com/SemanticMediaWiki/SemanticMediaWiki/tree/2.5.x/tests/phpunit/Integration/JSONScript/TestCases/s-0016.json) Test `Special:Ask` to produce correct printout position for `+|...` parameters (`wgContLang=en`, `wgLang=en`)

-- Last updated on 2017-07-11 by `readmeContentsBuilder.php`

<!-- End of generated contents by readmeContentsBuilder.php -->

## Designing an integration test

The `JSONScript` follows the arrange, act, assert approach, with the `setup` section
containing object definitions that are planned to be used during a test. The section
expects that an entity page and its contents (generally the page content in wikitext,
annotations etc.) to follow a predefined structure.

It is also possible to [import](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/phpunit/Integration/JSONScript/TestCases/p-0211.json) larger text passages or [upload files](https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/tests/phpunit/Integration/JSONScript/TestCases/p-0705.json)
for a test scenario.

When creating test scenarios, use disinct names and subjects to ensure that other
tests will not interfer with the expected results. It may also be of advantage to split
the setup of data (e.g. `Example/Test/1`) from the actual test subject (e.g. `Example/Test/Q.1`)
to avoid conflicating comparisons or false positive results during the assertion process.

<pre>
"setup": [
	{
		"page": "Has text",
		"namespace":"SMW_NS_PROPERTY",
		"contents": "[[Has type::Text]]"
	},
	{
		"page": "Property:Has number",
		"contents": "[[Has type::Number]]"
	},
	{
		"page": "Example/Test/1",
		"namespace":"NS_MAIN",
		"contents": "[[Has text::Some text to search]]"
	},
	{
		"page": "Example/Test/Q.1",
		"namespace":"NS_MAIN",
		"contents": "{{#ask: [[Has text::~Some text*]] |?Has text }}"
	}
],
</pre>

### Test assertions

* The `type` provides specialized assertion methods with some of them requiring
an extra setup to yield a comparable output but in most cases the `parser` type
should suffice to create test assertions for common test scenarios. Available types
are:
  * `query`, `concept`, and `format`
  * `parser`
  * `parser-html`
  * `rdf`
  * `special`
* The `about` describes what the test is expected to test which may help during
  a failure to identify potential conflicts or hints on how to resolve an issue.
* The `subject` refers to the page that was defined in the `setup` section.

For example, as of version 2 the `parser` type (`ParserTestCaseProcessor`) knows
two assertions methods:

- `assert-store` is to validate data against `Store::getSemanticData`
- `assert-output` is to validate string comparison against the `ParserOutput`
  generated text


#### Type `parser`
The test result assertion provides simplified string comparison methods (mostly for
output related assertion but expressive enough for users to understand the test
objective and its expected results). For example, verifying that the parser
does output a certain string, one has to the define an expected output.

<pre>
"tests": [
	{
		"type": "parser",
		"about": "#0 test output of the [[ ... ]] annotation",
		"subject": "Example/Test/1",
		"assert-output": {
			"to-contain": [
				"Some text to search"
			],
			"not-contain": [
				"abc"
			]
		}
	},
	{
		"type": "parser",
		"about": "#1 test output of #ask query",
		"subject": "Example/Test/Q.1",
		"assert-output": {
			"to-contain": [
				"Some text to search"
			],
			"not-contain": [
				"abc"
			]
		}
	}
]
</pre>

#### Type `parser-html`

To verify that the HTML code produced by the parser conforms to a certain
structure the test type `parser-html` may be used. With this type the expected
output structure may be specified as a CSS selector. The test will succeed if at
least one element according to that selector is found in the output.

Example:
<pre>
"tests": [
	{
		"type": "parser-html",
		"about": "#0 Basic List format",
		"subject": "Example/0401",
		"assert-output": {
			"to-contain": [
				"p > a[ title='Bar' ] + a[ title='Baz' ] + a[ title='Foo' ] + a[ title='Quok' ]"
			]
		}
	}
]
</pre>

For further details and limitations on the CSS selectors see the [description of
the Symfony CssSelector
Component](https://symfony.com/doc/current/components/css_selector.html) that is
used for this test type.

It is also possible to require an exact number of occurences of HTML elements by
providing an array instead of just a CSS selector string.

Example:
<pre>
		"assert-output": {
			"to-contain": [
				[ "p > a", 4 ]
			]
		}
</pre>

Finally the general well-formedness of the HTML can be tested, although this
will not fail for recoverable errors (see the [documentation on PHP's
DOMDocument::loadHTML](http://php.net/manual/en/domdocument.loadhtml.php#refsect1-domdocument.loadhtml-errors)).

Example:
<pre>
		"assert-output": {
			"to-be-valid-html": true,
		}
</pre>


### Preparing the test environment

It can happen that an output is mixed with language dependent content (site vs.
page content vs. user language) and therefore it is recommended to fix those
settings for a test by adding something like:

<pre>
"settings": {
	"wgContLang": "en",
	"wgLang": "en",
	"smwgNamespacesWithSemanticLinks": {
		"NS_MAIN": true,
		"SMW_NS_PROPERTY": true
	}
}
</pre>

By default not all settings parameter are enabled in `JsonTestCaseScriptRunner::prepareTest`
and may need to be extended in case a specific test case requires additional
customization.

Each `json` file requires a `meta` section with the `version` to correspond to the
`JsonTestCaseScriptRunner::getRequiredJsonTestCaseMinVersion` while `is-incomplete`
removes the file from the test plan if enabled.

The section also contains a `debug` flag and if enabled may output internal object
state information that can be helpful during debugging.

<pre>
"meta": {
	"version": "2",
	"is-incomplete": false,
	"debug": false
}
</pre>

### Skipping a test or mark as incomplete

Sometimes certain data can cause inconsistencies with an environment hence it is
possible to skip those cases by adding:

<pre>
{
	"skip-on": {
		"virtuoso": "Virtuoso 6.1 does not support BC/BCE dates"
	},
	"page": "Example/P0413/11",
	"contents": "[[Has date::Jan 1 300 BC]]"
},
</pre>

<pre>
{
	"skip-on": {
		"hhvm-*": "HHVM (or SQLite) shows opposite B1000, B9",
		"mw-1.28<": "`numeric` collation only available with 1.28+"
	}
}
</pre>

Constraints that include `hhvm-*` will indicate to exclude all HHVM versions while
`mw-1.28<` defines that any MW version lower than 1.28 is to be ignored.

It is also possible that an entire test scenario cannot be completed in a particular
environment therefore it can be marked and skipped with:

<pre>
"meta": {
	"skip-on": {
		"virtuoso": "Some info as to why it is skipped.",
		"sqlite": "...",
		"postgres": "..."
	},
	"version": "2",
	"is-incomplete": false,
	"debug": false
}
</pre>

If a test is incomplete for some reason, use the `is-incomplete` field to indicate
the status which henceforth avoids a test execution.

### File naming

The naming of a test file is arbitrary but it has been a good standard to indicate
the type of tests run. `s-0001.json` would indicate that the test is mostly concerned
with special pages while `p-0001.json` will handle with parser output assertions.

### Debugging and running a test

Generally, tests are run together with the `composer phpunit` execution but
it may not always be feasible especially when trying to debug or design a new test
case.

There are two methods that can help restrict the execution during the design or
debug phase:

* Modify the `JsonTestCaseScriptRunner::getAllowedTestCaseFiles`  to take an argument
such as a file name ( e.g. `s-0014.json`) to restrict the execution of a test which
is mostly done when running from an IDE editor
* The command line allows to invoke a filter argument to specify a case such as
`composer phpunit tests/phpunit/Integration/JSONScript/JsonTestCaseScriptRunnerTest -- --filter 's-0014.json'`

<pre>
$  composer integration -- --filter 's-0014.json'
Using PHP 5.6.8

Semantic MediaWiki: 2.5.0-alpha (SMWSQLStore3, mysql)
MediaWiki:          1.28.0-alpha (MediaWiki vendor autoloader)
Site language:      en

Execution time:     2017-01-01 12:00
Debug logs:         Enabled
Xdebug:             Disabled (or not installed)

phpunit 4.8.24 by Sebastian Bergmann and contributors.

Runtime:        PHP 5.6.8
Configuration:  ...\extensions\SemanticMediaWiki\phpunit.xml.dist

.

Time: 13.02 seconds, Memory: 34.00Mb

OK (1 test, 16 assertions)
</pre>

A general introduction to the test environment can be found [here](https://github.com/SemanticMediaWiki/SemanticMediaWiki/edit/master/tests/README.md).

## Technical notes

* The `JSON` is internally transformed into a corresponding `PHPUnit` dataset with
the help of the `JsonTestCaseContentHandler` and `JsonTestCaseScriptRunner`.
*  A test file (e.g "myTest.json") will be loaded from the specified location in
`JsonTestCaseScriptRunner::getTestCaseLocation` and is automatically run during
the `PHPUnit` test execution.
* The `readmeContentsBuilder.php` can be used to update the list of available test
cases including its descriptions.
