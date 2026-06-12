<?php
declare(strict_types=1);

namespace App;

use Stringable;

class Validator implements Stringable
{
    public bool $isValid {
        get => $this->valid;
    }

    protected(set) array $errors = [];

    public function __construct(
        protected array $rules,
        array $errors = [],
        protected bool  $valid = false
    ) {
        $this->errors = $errors;
    }

    public function validate(array $data): void
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            foreach ($this->parseRules($ruleString) as $rule) {
                $ruleParts = explode(':', (string) $rule, 2);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;

                if (!$this->$ruleName($data[$field] ?? null, $ruleValue)) {
                    $this->errors[$field][] = $this->getErrorMessage($field, $ruleName, $ruleValue);
                }
            }
        }

        $this->valid = empty($this->errors);
    }

    public function __toString(): string
    {
        $string = PHP_EOL;
        foreach ($this->errors as $field => $errors) {
            foreach ($errors as $error) {
                $string .= "$field: $error" . PHP_EOL;
            }
        }
        return $string;
    }

    protected function required(mixed $value): bool
    {
        return !is_null($value) && $value !== '';
    }

    protected function url(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function string(mixed $value): bool
    {
        return is_string($value);
    }

    protected function regex(mixed $value, ?string $pattern): bool
    {
        return is_string($value) && is_string($pattern) && preg_match($pattern, $value) === 1;
    }

    protected function getErrorMessage(string $field, string $rule, ?string $value): string
    {
        $messages = [
            'required' => "The $field field is required.",
            'url' => "The $field field must be a valid URL.",
            'string' => "The $field field must be a string.",
            'regex' => "The $field field must match the pattern $value.",
        ];

        return $messages[$rule] ?? "The $field field has an invalid value.";
    }

    private function parseRules(string $ruleString): array
    {
        $regexPos = strpos($ruleString, 'regex:');
        if ($regexPos === false) {
            return array_filter(explode('|', $ruleString));
        }

        $nonRegexPart = substr($ruleString, 0, $regexPos);
        $nonRegexRules = array_filter(explode('|', rtrim($nonRegexPart, '|')));

        return array_merge($nonRegexRules, [substr($ruleString, $regexPos)]);
    }
}
