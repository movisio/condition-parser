<?php
declare(strict_types = 1);

namespace Movisio\ConditionParser;

use Movisio\ConditionParser\ConditionOperator\Variable;
use Movisio\ConditionParser\ConditionOperator\Binary;
use Movisio\ConditionParser\ConditionOperator\Value;
use Movisio\ConditionParser\ConditionOperator\Unary;
use Movisio\ConditionParser\ConditionOperator\Property;
use Movisio\ConditionParser\ConditionOperator\OperatorInterface;

/**
 * Class for parsing and evaluating user conditions
 */
class ConditionParser
{

    /**
     * parses condition and returns evaluable expression tree
     * tree implements toString() for debugging
     *
     * @param string $condition
     * @return OperatorInterface
     * @throws \Exception
     */
    public static function parse(string $condition) : OperatorInterface
    {
        if (empty($condition)) {
            throw new \InvalidArgumentException('Condition can not be empty');
        }
        // needed for tokenizer
        if (strpos($condition, '<?php') !== 0) {
            $condition = '<?php ' . $condition;
        }
        $tokens = token_get_all($condition);
        return static::buildTree($tokens);
    }

    /**
     * order of operations
     * @var array
     */
    protected static $precedence = [
        '(' => 1,
        ')' => 1,
        '<' => 15,
        '>' => 15,
        '!' => 100,
        T_BOOLEAN_AND => 6,
        T_BOOLEAN_OR => 5,
        T_IS_SMALLER_OR_EQUAL => 15,
        T_IS_GREATER_OR_EQUAL => 15,
        T_IS_EQUAL => 15,
        T_IS_NOT_EQUAL => 15,
        T_EMPTY => 100,
        T_OBJECT_OPERATOR => 100,
    ];

    /**
     * arity of operators
     * @var array
     */
    protected static $opArity = [
        '(' => 1,
        ')' => 1,
        '<' => 2,
        '>' => 2,
        T_IS_SMALLER_OR_EQUAL => 2,
        T_IS_GREATER_OR_EQUAL => 2,
        T_IS_EQUAL => 2,
        T_IS_NOT_EQUAL => 2,
        T_BOOLEAN_OR => 2,
        T_BOOLEAN_AND => 2,
        T_OBJECT_OPERATOR => 2,
        '!' => 1,
    ];

    /**
     * Works alike to transforming infix to postfix but instead of postfix expression
     * builds expression tree directly
     *
     * @param array $tokens
     * @return OperatorInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    protected static function buildTree(array $tokens) : OperatorInterface
    {
        // partially generated trees
        $stack = [];
        // operators with lower priority
        $opStack = [];
        foreach ($tokens as $token) {
            // skipping these
            if (is_array($token) && ($token[0] === T_WHITESPACE)) {
                continue;
            }
            if (is_array($token) && ($token[0] === T_OPEN_TAG)) {
                continue;
            }
            // value of T_* constant or string for single-char tokens
            $tokenId = is_array($token) ? $token[0] : $token;
            // string from T_* constant or token
            $tokenName = is_array($token) ? token_name($token[0]) : $token;

            // T_STRING is returned for function names, reserved words etc
            // we read only true and false as consts
            // isNull or functions have to be hadnled differently
            if ($tokenId === T_STRING) {
                switch (strtolower($token[1])) {
                    case 'true':
                        $tokenId = T_CONST;
                        $token[1] = true;
                        break;
                    case 'false':
                        $tokenId = T_CONST;
                        $token[1] = false;
                        break;

                    default:
                        break;
                }
            }
            switch ($tokenId) {
                case T_VARIABLE:
                    $varName = str_replace('$', '', $token[1]);
                    $var = new Variable($varName);
                    array_push($stack, $var);
                    break;
                case T_STRING:
                    $varName = $token[1];
                    /** @var string $varName */
                    $var = new Property($varName);
                    array_push($stack, $var);
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                    // contains string + opening and closing "/'
                    $token[1] = trim($token[1], '"\'');
                    // no break
                case T_LNUMBER:
                case T_DNUMBER:
                case T_CONST:
                    $val = new Value($token[1]);
                    array_push($stack, $val);
                    break;
                case T_BOOLEAN_AND:
                case T_BOOLEAN_OR:
                case '<':
                case T_IS_SMALLER_OR_EQUAL:
                case '>':
                case T_IS_GREATER_OR_EQUAL:
                case T_IS_EQUAL:
                case T_IS_NOT_EQUAL:
                case '!':
                case T_EMPTY:
                case T_OBJECT_OPERATOR:
                    // read all lower priority (higher precedence) operators from stack
                    // and create trees
                    while (count($opStack) && (static::$precedence[$tokenId] < static::$precedence[end($opStack)])) {
                        $op = array_pop($opStack);
                        $r = array_pop($stack);
                        if (static::$opArity[$op] == 2) {
                            $l = array_pop($stack);
                            $binOp = new Binary($op, $l, $r);
                            array_push($stack, $binOp);
                        } else {
                            $unOp = new Unary($op, $r);
                            array_push($stack, $unOp);
                        }
                    }
                    array_push($opStack, $tokenId);
                    break;
                case '(':
                    array_push($opStack, $tokenId);
                    break;
                case ')':
                    // process all operators from this level of parens
                    while (count($opStack) && (end($opStack) != '(')) {
                        $op = array_pop($opStack);
                        $r = array_pop($stack);
                        if (static::$opArity[$op] == 2) {
                            $l = array_pop($stack);
                            $binOp = new Binary($op, $l, $r);
                            array_push($stack, $binOp);
                        } else {
                            $unOp = new Unary($op, $r);
                            array_push($stack, $unOp);
                        }
                    }
                    // remove opening parenthesis
                    array_pop($opStack);
                    break;

                default:
                    throw new \InvalidArgumentException("Can not parse $tokenName");
                    break;
            }
        }
        // process remaining operators
        // they are guaranteed to be ordered by priority
        while (count($opStack)) {
            //var_dump($opStack, $stack);
            $op = array_pop($opStack);
            $r = array_pop($stack);
            if (static::$opArity[$op] == 2) {
                $l = array_pop($stack);
                $binOp = new Binary($op, $l, $r);
                array_push($stack, $binOp);
            } else {
                $unOp = new Unary($op, $r);
                array_push($stack, $unOp);
            }
        }
        // there should be only one operand now - root of expression tree
        if (count($stack) !== 1) {
            throw new \Exception('Internal error - too many results');
        }
        return reset($stack);
    }
}
