<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Requests;

class RuleValidator
{
    /** @var array<string, string> */
    private $errors = [];

    /**
     * Validate the given data against the rules matrix.
     *
     * @param array<string, mixed> $data
     * @param array<string, string|array<string>> $rules
     * @return array<string, string> Dictionary of failed field validations
     */
    public function validate(array $data, array $rules, array $customMessages = []): array
    {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            $rulesArray = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $data[$field] ?? null;

            $isNullable = in_array('nullable', $rulesArray, true);
            $isSometimes = in_array('sometimes', $rulesArray, true);
            $hasField = array_key_exists($field, $data);

            if ($isSometimes && !$hasField) {
                continue;
            }

            if ($value === null || $value === '') {
                if (in_array('required', $rulesArray, true)) {
                    $this->errors[$field] = $this->getErrorMessage($field, 'required', null, $data, $customMessages);
                    continue;
                }
                if ($isNullable) {
                    continue;
                }
            }

            foreach ($rulesArray as $ruleBlock) {
                if ($ruleBlock === 'required' || $ruleBlock === 'nullable' || $ruleBlock === 'sometimes') {
                    continue;
                }

                $parts = explode(':', $ruleBlock, 2);
                $ruleName = $parts[0];
                $ruleParam = $parts[1] ?? null;

                $methodName = 'validate' . str_replace('_', '', ucwords($ruleName, '_'));
                if (method_exists($this, $methodName)) {
                    $isValid = $this->$methodName($value, $ruleParam, $data, $field);
                    if (!$isValid) {
                        $this->errors[$field] = $this->getErrorMessage($field, $ruleName, $ruleParam, $data, $customMessages);
                        break;
                    }
                }
            }
        }

        return $this->errors;
    }

    // Core Type Validators
    private function validateString($value): bool { return is_string($value); }
    private function validateInteger($value): bool { return is_int($value) || (is_string($value) && ctype_digit($value)); }
    private function validateNumeric($value): bool { return is_numeric($value); }
    private function validateBoolean($value): bool { return is_bool($value) || in_array($value, [1, 0, '1', '0', true, false], true); }
    private function validateArray($value): bool { return is_array($value); }
    private function validateEmail($value): bool { return (bool) filter_var($value, FILTER_VALIDATE_EMAIL); }
    private function validateUrl($value): bool { return (bool) filter_var($value, FILTER_VALIDATE_URL); }
    private function validateJson($value): bool { if (!is_string($value)) { return false; } json_decode($value); return json_last_error() === JSON_ERROR_NONE; }
    private function validateUuid($value): bool { return is_string($value) && (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value); }

    // Numeric bounds
    private function validateMin($value, ?string $param): bool { return $param !== null && is_numeric($value) && (float)$value >= (float)$param; }
    private function validateMax($value, ?string $param): bool { return $param !== null && is_numeric($value) && (float)$value <= (float)$param; }
    private function validateBetween($value, ?string $param): bool { if ($param === null) return false; $p = explode(',', $param); return is_numeric($value) && (float)$value >= (float)($p[0] ?? 0) && (float)$value <= (float)($p[1] ?? 0); }

    // String lengths
    private function validateMinLength($value, ?string $param): bool { return $param !== null && is_string($value) && mb_strlen($value) >= (int)$param; }
    private function validateMaxLength($value, ?string $param): bool { return $param !== null && is_string($value) && mb_strlen($value) <= (int)$param; }

    // Inclusions & Regex
    private function validateIn($value, ?string $param): bool { return $param !== null && in_array((string)$value, explode(',', $param), true); }
    private function validateNotIn($value, ?string $param): bool { return $param !== null && !in_array((string)$value, explode(',', $param), true); }
    private function validateRegex($value, ?string $param): bool { return $param !== null && is_string($value) && (bool) preg_match($param, $value); }

    // String alpha constraints
    private function validateAlpha($value): bool { return is_string($value) && (bool) preg_match('/^[a-zA-Z\p{L}]+$/u', $value); }
    private function validateAlphaNum($value): bool { return is_string($value) && (bool) preg_match('/^[a-zA-Z0-9\p{L}]+$/u', $value); }
    private function validateAlphaDash($value): bool { return is_string($value) && (bool) preg_match('/^[a-zA-Z0-9\p{L}_-]+$/u', $value); }

    // Digits checks
    private function validateDigits($value, ?string $param): bool { return $param !== null && (is_int($value) || is_string($value)) && ctype_digit((string)$value) && strlen((string)$value) === (int)$param; }
    private function validateDigitsBetween($value, ?string $param): bool { if ($param === null || (!is_int($value) && !is_string($value)) || !ctype_digit((string)$value)) return false; $p = explode(',', $param); $len = strlen((string)$value); return $len >= (int)($p[0] ?? 0) && $len <= (int)($p[1] ?? 0); }

    // Network IPs
    private function validateIp($value): bool { return (bool) filter_var($value, FILTER_VALIDATE_IP); }
    private function validateIpv4($value): bool { return (bool) filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4); }
    private function validateIpv6($value): bool { return (bool) filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6); }

    // State states checks
    private function validateAccepted($value): bool { return in_array($value, [true, 1, '1', 'yes', 'on'], true); }
    private function validateDeclined($value): bool { return in_array($value, [false, 0, '0', 'no', 'off'], true); }
    private function validateFilled($value): bool { return $value !== null && $value !== ''; }
    private function validatePresent($value, ?string $param, array $data, string $field): bool { return array_key_exists($field, $data); }
    private function validateProhibited($value, ?string $param, array $data, string $field): bool { return !array_key_exists($field, $data) || $value === null || $value === ''; }

    // Confirmed matching rule
    private function validateConfirmed($value, ?string $param, array $data, string $field): bool
    {
        $confirmationField = $field . '_confirmation';
        return isset($data[$confirmationField]) && $value === $data[$confirmationField];
    }

    // Date validators engine layer
    private function validateDate($value): bool { if ($value instanceof \DateTimeInterface) return true; if (!is_string($value)) return false; return strtotime($value) !== false; }
    private function validateDateFormat($value, ?string $param): bool { if ($param === null || !is_string($value)) return false; $d = \DateTime::createFromFormat($param, $value); return $d && $d->format($param) === $value; }
    private function validateBefore($value, ?string $param): bool
    {
        if ($param === null || !$this->validateDate($value)) {
            return false;
        }

        $vTime = $value instanceof \DateTimeInterface
            ? $value->getTimestamp()
            : (is_string($value) ? strtotime($value) : false);

        $pTime = strtotime($param);

        return $pTime !== false && $vTime !== false && $vTime < $pTime;
    }
    private function validateAfter($value, ?string $param): bool
    {
        if ($param === null || !$this->validateDate($value)) {
            return false;
        }

        $vTime = $value instanceof \DateTimeInterface
            ? $value->getTimestamp()
            : (is_string($value) ? strtotime($value) : false);

        $pTime = strtotime($param);

        return $pTime !== false && $vTime !== false && $vTime > $pTime;
    }

    // Database simulation layer hooks placeholders (evaluated dynamic custom closures inside requests context rules if needed)
    private function validateUnique(): bool { return true; }
    private function validateExists(): bool { return true; }

    // Files parameters
    private function validateMimetypes($value, ?string $param): bool { if ($param === null || !is_array($value) || !isset($value['type'])) return false; return in_array($value['type'], explode(',', $param), true); }
    private function validateSize($value, ?string $param): bool { if ($param === null || !is_array($value) || !isset($value['size'])) return false; return $value['size'] <= ((int)$param * 1024); }

    /**
     * Smart translation parsing token placeholders replacing strategy execution.
     */
    private function getErrorMessage(string $field, string $rule, ?string $param, array $data, array $customMessages): string
    {
        $messageKey = $field . '.' . $rule;

        if (isset($customMessages[$messageKey])) {
            $message = $customMessages[$messageKey];
        } elseif (isset($customMessages[$field])) {
            $message = $customMessages[$field];
        } else {
            $defaults = [
                'required'       => 'The :field field is required.',
                'string'         => 'The :field field must be a string.',
                'integer'        => 'The :field field must be an integer.',
                'numeric'        => 'The :field field must be a number.',
                'boolean'        => 'The :field field must be true or false.',
                'array'          => 'The :field field must be an array.',
                'email'          => 'The :field field must be a valid email address.',
                'url'            => 'The :field field must be a valid URL.',
                'min'            => 'The :field field must be at least :min.',
                'max'            => 'The :field field must not be greater than :max.',
                'min_length'     => 'The :field field must be at least :min characters.',
                'max_length'     => 'The :field field must not exceed :max characters.',
                'in'             => 'The :field field must be one of: :values.',
                'size'           => 'The :field file must not exceed :size KB.',
                'mimetypes'      => 'The :field file must be of type: :types.',
                'regex'          => 'The :field field format is invalid.',
                'confirmed'      => 'The :field confirmation does not match.',
                'unique'         => 'The :field has already been taken.',
                'exists'         => 'The selected :field is invalid.',
                'date'           => 'The :field field must be a valid date.',
                'date_format'    => 'The :field field must match the format :format.',
                'before'         => 'The :field field must be a date before :date.',
                'after'          => 'The :field field must be a date after :date.',
                'alpha'          => 'The :field field must only contain letters.',
                'alpha_num'      => 'The :field field must only contain letters and numbers.',
                'alpha_dash'     => 'The :field field must only contain letters, numbers, dashes and underscores.',
                'digits'         => 'The :field field must be :digits digits.',
                'digits_between' => 'The :field field must be between :min and :max digits.',
                'between'        => 'The :field field must be between :min and :max.',
                'not_in'         => 'The selected :field is invalid.',
                'ip'             => 'The :field field must be a valid IP address.',
                'ipv4'           => 'The :field field must be a valid IPv4 address.',
                'ipv6'           => 'The :field field must be a valid IPv6 address.',
                'json'           => 'The :field field must be a valid JSON string.',
                'uuid'           => 'The :field field must be a valid UUID.',
                'accepted'       => 'The :field field must be accepted.',
                'declined'       => 'The :field field must be declined.',
                'filled'         => 'The :field field must have a value when present.',
                'present'        => 'The :field field must be present.',
                'prohibited'     => 'The :field field is prohibited.',
            ];

            $message = $defaults[$rule] ?? 'The :field field configuration is invalid.';
        }

        $niceField = str_replace('_', ' ', $field);
        $replacements = [':field' => $niceField];

        if ($param !== null) {
            if (in_array($rule, ['between', 'digits_between'], true)) {
                $p = explode(',', $param);
                $replacements[':min'] = $p[0] ?? '';
                $replacements[':max'] = $p[1] ?? '';
            } else {
                $replacements[':min']    = $param;
                $replacements[':max']    = $param;
                $replacements[':size']   = $param;
                $replacements[':digits'] = $param;
                $replacements[':format'] = $param;
                $replacements[':date']   = $param;
                $replacements[':values'] = str_replace(',', ', ', $param);
                $replacements[':types']  = str_replace(',', ', ', $param);
            }
        }

        return strtr($message, $replacements);
    }
}
