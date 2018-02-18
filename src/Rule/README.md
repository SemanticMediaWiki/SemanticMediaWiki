The objective of the rule namespace is to allow for a structured definition of rules where rules depending in the interpreter can support different schema and
syntax elements.

The rule namespace and its definition expect a JSON format as input to ensure that content elements are structured and with the help of a [JSON schema][json:schema] can enforce the requirements of a specific rule type.

When storing a rule, the following properties will be annotated given those elements are part of the rule definition.

* Rule type (`_RL_TYPE` )
* Rule definition (`_RL_DEF`)
* Rule description (`_RL_DESC`)
* Rule tag (`_RL_TAG`)

## Registration

To provide extensibility for new rule interpreters, the `$smwgRuleTypes` setting registers all types and their specification.

```
$GLOBALS['smwgRuleTypes'] = [
	'LINK_FORMAT_RULE' => [
		'schema'  => __DIR__ . '/data/schema/rule/...',
		'group'   => SMW_RULE_GROUP_FORMAT,
	]
];
```

[json:schema]: http://json-schema.org/
