<?php
declare(strict_types = 1);

namespace Movisio\ConditionParser\ConditionOperator;

/**
 * Constant value expression
 */
class Value implements OperatorInterface
{
    /** @var mixed value */
    protected $value;

    /**
     * Return value
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     * @param \ArrayAccess $variables
     * @return mixed
     */
    public function evaluate(\ArrayAccess $variables)
    {
        return $this->value;
    }

    /**
     * @param mixed $value constant value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        if (is_bool($this->value)) {
            return $this->value ? 'true' : 'false';
        }
        if (is_string($this->value)) {
            return "'" . $this->value . "'";
        }

        return (string) $this->value;
    }
}
