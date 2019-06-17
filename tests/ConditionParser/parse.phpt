<?php
require __DIR__ . '/../../vendor/autoload.php';

use Tester\Assert;
use Tester\Environment;
use Tester\Expect;
use Movisio\ConditionParser\ConditionParser;

Environment::setup();

class ConditionParserTests extends Tester\TestCase
{
    /*
     * data provider
     * 3 parameters - conditiom, test input, expected output
     */
    public function getValidConditions()
    {
        return [
            ['$cancelled == 1 && $id != 1', ['cancelled' => 1, 'id' => 0], true],
            ['$cancelled != 1 && $id > 1', ['cancelled' => 2, 'id' => 1], false],
            // constants
            ['true', [], true],
            ['false', [], false],
            // neg
            ['!true', [], false],
            ['!false', [], true],
            ['! true', [], false],
            ['! false', [], true],
            ['!!true', [], true],
            ['!!false', [], false],
            ['! !true', [], true],
            ['! !false', [], false],
            ['!! true', [], true],
            ['!! false', [], false],
            ['! ! true', [], true],
            ['! ! false', [], false],
            // equality
            ['1 == 1', [], true],
            ['1 == "1"', [], true],
            ['1 == 2', [], false],
            ['1 == "2"', [], false],
            ['1 != 1.1', [], true],
            // inequality
            ['1 != 1', [], false],
            ['1 != "1"', [], false],
            ['1 != 2', [], true],
            ['1 != "2"', [], true],
            ['1 != 1.1', [], true],
            // comparisons
            ['1 < 10', [], true],
            ['10 < 1', [], false],
            ['1 < 1', [], false],
            ['1 < 1.01', [], true],
            ['1 <= 10', [], true],
            ['10 <= 1', [], false],
            ['1 <= 1', [], true],
            ['1 > 10', [], false],
            ['10 > 1', [], true],
            ['1 > 1', [], false],
            ['1 >= 10', [], false],
            ['10 >= 1', [], true],
            ['1 >= 1', [], true],
            // variables
            ['$a', ['a' => false], false],
            ['$a', ['a' => true], true],
            ['1 == $a', ['a' => 0], false],
            ['1 == $a', ['a' => 1], true],
            ['1 == $a', ['a' => '1'], true],
            // and
            ['$a && $b', ['a' => false, 'b' => false], false],
            ['$a && $b', ['a' => true, 'b' => false], false],
            ['$a && $b', ['a' => false, 'b' => true], false],
            ['$a && $b', ['a' => true, 'b' => true], true],
            // or
            ['$a || $b', ['a' => false, 'b' => false], false],
            ['$a || $b', ['a' => true, 'b' => false], true],
            ['$a || $b', ['a' => false, 'b' => true], true],
            ['$a || $b', ['a' => true, 'b' => true], true],
            // combination
            ['$a || $b && $c', ['a' => false, 'b' => false, 'c' => false], false],
            ['$a || $b && $c', ['a' => false, 'b' => false, 'c' => true], false],
            ['$a || $b && $c', ['a' => false, 'b' => true, 'c' => false], false],
            ['$a || $b && $c', ['a' => false, 'b' => true, 'c' => true], true],
            ['$a || $b && $c', ['a' => true, 'b' => false, 'c' => false], true],
            ['$a || $b && $c', ['a' => true, 'b' => false, 'c' => true], true],
            ['$a && $b || $c', ['a' => false, 'b' => false, 'c' => false], false],
            ['$a && $b || $c', ['a' => false, 'b' => false, 'c' => true], true],
            ['$a && $b || $c', ['a' => false, 'b' => true, 'c' => false], false],
            ['$a && $b || $c', ['a' => true, 'b' => true, 'c' => false], true],
            ['$a && $b || $c', ['a' => true, 'b' => false, 'c' => true], true],
            // parens 1
            ['($a || $b) && $c', ['a' => false, 'b' => false, 'c' => false], false],
            ['($a || $b) && $c', ['a' => false, 'b' => false, 'c' => true], false],
            ['($a || $b) && $c', ['a' => false, 'b' => true, 'c' => false], false],
            ['($a || $b) && $c', ['a' => true, 'b' => false, 'c' => false], false],
            ['($a || $b) && $c', ['a' => true, 'b' => false, 'c' => true], true],
            // parens 2
            ['($a && $b) || $c', ['a' => false, 'b' => false, 'c' => false], false],
            ['($a && $b) || $c', ['a' => false, 'b' => false, 'c' => true], true],
            ['($a && $b) || $c', ['a' => false, 'b' => true, 'c' => false], false],
            ['($a && $b) || $c', ['a' => true, 'b' => true, 'c' => false], true],
            ['($a && $b) || $c', ['a' => true, 'b' => false, 'c' => true], true],
            // object dereference
            ['$a->x', ['a' => new ArrayObject(['x' => false])], false],
            ['$a->x', ['a' => new ArrayObject(['x' => true])], true],
            // empty
            ['empty($a)', ['a' => new ArrayObject(['x' => false])], false],
        ];
    }

    /*
     * data provider
     * 2 parameters - input condition, expected output
     */
    public function getConditionsToString()
    {
        return [
            ['$cancelled == 1 && $id != 1', '(($cancelled == 1) && ($id != 1))'],
            ['$cancelled != 1 && $id > 1', '(($cancelled != 1) && ($id > 1))'],
            // constants
            ['true', 'true'],
            ['false', 'false'],
            ['"abc"', '\'abc\''],
            // neg
            ['!true', '(! true)'],
            ['! true', '(! true)'],
            ['!!true', '(! (! true))'],
            ['! !true', '(! (! true))'],
            ['!! true', '(! (! true))'],
            ['! ! true', '(! (! true))'],
            // equality
            ['1 == 1', '(1 == 1)'],
            ['1 == "1"', '(1 == \'1\')'],
            // inequality
            ['1 != 1', '(1 != 1)'],
            // comparisons
            ['1 < 10', '(1 < 10)'],
            ['1 <= 10', '(1 <= 10)'],
            ['1 > 10', '(1 > 10)'],
            ['1 >= 10', '(1 >= 10)'],
            // variables
            ['$a', '$a'],
            ['1 == $a', '(1 == $a)'],
            // and
            ['$a && $b', '($a && $b)'],
            // or
            ['$a || $b', '($a || $b)'],
            //combination
            ['$a || $b && $c', '($a || ($b && $c))'],
            ['$a && $b || $c', '(($a && $b) || $c)'],
            // parens 1
            ['($a || $b) && $c', '(($a || $b) && $c)'],
            // parens 2
            ['($a && $b) || $c', '(($a && $b) || $c)'],
            // object dereference
            ['$a->x', '($a->x)'],
            // empty
            ['empty($a)', '(empty($a))'],
        ];
    }

    /*
     * execute before each test
     */
    public function setUp()
    {
    }

    /*
     * execute after each test
     */
    public function tearDown()
    {
    }

    /**
     * is it possible to evaluate() the parsed condition?
     * only first field used from dataProvider
     * @dataProvider getValidConditions
     */
    public function testOne($condition)
    {
          Assert::true(
              method_exists(ConditionParser::parse($condition), 'evaluate'),
              'Class does not have method "evaluate"'
          );
    }

    /**
     * does the parsed expression object implement OperatorInterface ?
     * @dataProvider getValidConditions
     */
    public function testTwo($condition)
    {
          Assert::equal(
            Expect::that(function ($value) {
              if (isset(class_implements($value)['Movisio\ConditionParser\ConditionOperator\OperatorInterface'])) {
                return true;
              }
              return false;
            }),
            ConditionParser::parse($condition)
          );
    }

    /**
     * test valid conditions and their return values
     * @dataProvider getValidConditions
     */
    public function testThree($condition, $testValues, $expectedResult)
    {
        $expression = ConditionParser::parse($condition);
        $conditionObject = new ArrayObject($testValues);
        Assert::equal($expression->evaluate($conditionObject), $expectedResult);
    }

    /**
     * test toString
     * @dataProvider getConditionsToString
     */
    public function testToString($condition, $expectedResult)
    {
        $expression = ConditionParser::parse($condition);
        Assert::equal((string)$expression, $expectedResult);
    }

    /**
     * test invalid inputs
     */
    public function testExceptions()
    {
      Assert::exception(function () { $expression = ConditionParser::parse(""); }, InvalidArgumentException::class, '#empty#i');
      Assert::exception(function () { $expression = ConditionParser::parse("*"); }, InvalidArgumentException::class, '#\*#');

      $expression = ConditionParser::parse('$a');
      Assert::exception(function () use ($expression) { $expression->evaluate(); }, ArgumentCountError::class);

      Assert::exception(function () use ($expression) { $expression->evaluate(new ArrayObject([])); }, InvalidArgumentException::class, '#not defined#i');

      $expression = ConditionParser::parse('$a->b');
      Assert::exception(function () use ($expression) { $expression->evaluate(new ArrayObject(['a' => 1])); }, TypeError::class, '#ArrayAccess#i');
      Assert::exception(function () use ($expression) { $expression->evaluate(new ArrayObject(['a' => new ArrayObject([])])); }, InvalidArgumentException::class, '#not defined#i');

      $expression = new \Movisio\ConditionParser\ConditionOperator\Unary('-', new \Movisio\ConditionParser\ConditionOperator\Value(1));
      Assert::exception(function () use ($expression) { $expression->evaluate(new ArrayObject([])); }, Exception::class, '#not implemented#i');
      Assert::exception(function () use ($expression) { $expression->evaluate(new ArrayObject([])); }, Exception::class, '#-#i');

      $tmp = (string)$expression;
      Assert::match('#1#i', $tmp);
      Assert::match('#\-#i', $tmp);

      $expression = new \Movisio\ConditionParser\ConditionOperator\Binary('+', new \Movisio\ConditionParser\ConditionOperator\Value(1), new \Movisio\ConditionParser\ConditionOperator\Value(1));
      Assert::exception(function () use ($expression) { $expression->evaluate(new ArrayObject([])); }, Exception::class, '#not implemented#i');
      Assert::exception(function () use ($expression) { $expression->evaluate(new ArrayObject([])); }, Exception::class, '#\+#i');

      $tmp = (string)$expression;
      Assert::equal('(1 + 1)', $tmp);
    }
}

(new ConditionParserTests)->run();
