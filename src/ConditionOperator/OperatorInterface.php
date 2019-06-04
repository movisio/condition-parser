<?php
declare(strict_types = 1);

namespace Movisio\ConditionParser\ConditionOperator;

/**
 * based on shunting-yard algorithm modification from
 *   http://cplusplus.kurttest.com/notes/stack.html#binary-expression-tree
 *
 * @notImplemented arithmetic expressions, notNull, right-associative operators...
 * for possible future unary minus http://stackoverflow.com/questions/5239715/problems-with-a-shunting-yard-algorithm
 */
interface OperatorInterface
{
    /**
     * Evaluates entire subtree
     * uses php lazy evaluation so in some cases evaluation is succesful even
     * when some variables have no values
     *
     * @param \ArrayAccess $variables values to substitute for variables
     * @return mixed
     * @throws \InvalidArgumentException on undefined value for evaluated variable
     * @throws \Exception on unknown or unimplemented operator
     */
    public function evaluate(\ArrayAccess $variables);
}
