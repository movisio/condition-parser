# Condition Parser [![Build Status](https://travis-ci.org/movisio/condition-parser.svg?branch=master)](https://travis-ci.org/movisio/condition-parser)

A simple condition parsing and evaluation library. Shunting-yard algorithm for parsing strings to expression trees that can ben evaluated later. Supports variables that can be set for each evaluation.

Example usage:
```
$expression = ConditionParser::parse(̈́'$deleted == 0 && $id > 1');
$conditionObject = new ArrayObject($userEntityData);
$can_be_deleted = $expression->evaluate($conditionObject)
```
Once parsed $expression object can be evaluated multiple times with different data.
The `parse()` method requires an object implementing the \ArrayAccess interface currently because we use it mostly with ORM entities that can implement it and because at the moment it is not possible to type-hint to `array` and `\ArrayAccess` at the same time. 
