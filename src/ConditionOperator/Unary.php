<?php
declare(strict_types = 1);

namespace Movisio\ConditionParser\ConditionOperator;

/**
 * Unary expression tree
 * only NOT and EMPTY operators implemented
 */
class Unary implements OperatorInterface
{

    /** @var OperatorInterface subexpression */
    protected $subExpr;

    /** @var string|int - PHP token operator */
    protected $operator;

    /**
     * Evaluate expression recursively
     * @param \ArrayAccess $variables
     * @return mixed
     * @throws \Exception
     */
    public function evaluate(\ArrayAccess $variables)
    {
        switch ($this->operator) {
            case T_EMPTY:
                return ! count($this->subExpr->evaluate($variables));
            break;
            case '!':
                return ! $this->subExpr->evaluate($variables);
            break;
            default:
                throw new \Exception("Unary operator $this->operator not implemented");
            break;
        }
    }

    /**
     * @param string|int $operator token
     * @param \Core\Utils\ConditionOperator\OperatorInterface $subExpr
     */
    public function __construct($operator, OperatorInterface $subExpr)
    {
        $this->operator = $operator;
        $this->subExpr = $subExpr;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return "($this->operator $this->subExpr)";
    }
}
