<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data) { $this->data = $data; }

    public static function make(array $data): self
    {
        return new self($data);
    }

    public function check(string $field, $value, array $rules): self
    {
        foreach ($rules as $rule) {
            if (isset($this->errors[$field])) break;
            $param = null;
            if (str_contains($rule, ':')) {
                [$rule, $param] = explode(':', $rule, 2);
            }
            $this->apply($field, $value, $rule, $param);
        }
        return $this;
    }

    public function field(string $field, array $rules): self
    {
        return $this->check($field, $this->data[$field] ?? null, $rules);
    }

    private function apply(string $field, $value, string $rule, ?string $param): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    $this->errors[$field] = 'This field is required.';
                }
                break;
            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->errors[$field] = 'Must be a string.';
                }
                break;
            case 'min':
                if (is_string($value) && mb_strlen($value) < (int) $param) {
                    $this->errors[$field] = "Must be at least {$param} characters.";
                }
                break;
            case 'max':
                if (is_string($value) && mb_strlen($value) > (int) $param) {
                    $this->errors[$field] = "Must be at most {$param} characters.";
                }
                break;
            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = 'Must be a valid email.';
                }
                break;
            case 'url':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->errors[$field] = 'Must be a valid URL.';
                }
                break;
            case 'in':
                $allowed = explode(',', (string) $param);
                if ($value !== null && !in_array((string) $value, $allowed, true)) {
                    $this->errors[$field] = 'Invalid value.';
                }
                break;
            case 'integer':
                if ($value !== null && $value !== '' && !ctype_digit((string) $value)) {
                    $this->errors[$field] = 'Must be an integer.';
                }
                break;
            case 'alpha_dash':
                if ($value !== null && $value !== '' && !preg_match('/^[\pL\pN_-]+$/u', (string) $value)) {
                    $this->errors[$field] = 'Only letters, numbers, dashes and underscores.';
                }
                break;
            case 'confirmed':
                $other = $this->data[$field . '_confirmation'] ?? null;
                if ($value !== $other) {
                    $this->errors[$field] = 'Confirmation does not match.';
                }
                break;
            case 'regex':
                if ($value !== null && $value !== '' && !preg_match((string) $param, (string) $value)) {
                    $this->errors[$field] = 'Invalid format.';
                }
                break;
            case 'boolean':
                if ($value !== null && !in_array($value, ['0', '1', 0, 1, true, false, 'true', 'false'], true)) {
                    $this->errors[$field] = 'Must be boolean.';
                }
                break;
        }
    }

    public function fails(): bool { return !empty($this->errors); }
    public function errors(): array { return $this->errors; }
    public function validated(): array { return $this->data; }

    public function withMessage(string $field, string $msg): self
    {
        $this->errors[$field] = $msg;
        return $this;
    }
}
