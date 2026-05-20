<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Exceptions;

class ValidationException extends \Exception
{
    /** @var array<string, string> */
    private $errors = [];

    /**
     * Set failed fields validation messages.
     *
     * @param array<string, string> $errors
     */
    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }

    /**
     * Get failed fields validation messages map.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}