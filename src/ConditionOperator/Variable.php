<?php
declare(strict_types = 1);

namespace Movisio\ConditionParser\ConditionOperator;

/**
 * Variable expression
 */
class Variable implements OperatorInterface
{
    /** @var string variable */
    protected $name;

    /**
     * Return variable value
     * @param \ArrayAccess $variables
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function evaluate(\ArrayAccess $variables)
    {
        if (!$variables->offsetExists($this->name)) {
            throw new \InvalidArgumentException("Variable '$this->name' was not defined");
        }
        return $variables[$this->name];
    }

    /**
     * @param string $name variable name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return '$' . $this->name;
    }
}
