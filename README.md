# Condition Parser [![Tests](https://github.com/movisio/condition-parser/workflows/Tests/badge.svg?branch=master)](https://github.com/movisio/condition-parser/actions) [![Coverage Status](https://coveralls.io/repos/github/movisio/condition-parser/badge.svg?branch=master)](https://coveralls.io/github/movisio/condition-parser?branch=master)

A simple condition parsing and evaluation library. Shunting-yard algorithm for parsing strings to expression trees that can ben evaluated later. Supports variables that can be set for each evaluation.

Installation:
```
composer require movisio/condition-parser
```

Example usage:
```
$expression = ConditionParser::parse('$deleted == 0 && $id > 1');
$conditionObject = new ArrayObject($userEntityData);
$can_be_deleted = $expression->evaluate($conditionObject)
```
Once parsed $expression object can be evaluated multiple times with different data.
The `parse()` method requires an object implementing the \ArrayAccess interface currently because we use it mostly with ORM entities that can implement it and because at the moment it is not possible to type-hint to `array` and `\ArrayAccess` at the same time. 

v1.0.2
- tests for invalid inputs
- remove forbidden throw from __toString()
- add parsing of null

v1.0.1
- more unit tests
- fixes
  - better handling of int and float constants including toString() not quoting numbers
  - fix exception on empty($x) and toString() on UnaryOperator

v1.0.0 - initial release
