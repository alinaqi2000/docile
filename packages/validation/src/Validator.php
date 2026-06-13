<?php

declare(strict_types=1);

namespace Docile\Validation;

use Docile\Validation\Attribute\Confirmed;
use Docile\Validation\Attribute\Email;
use Docile\Validation\Attribute\FloatType;
use Docile\Validation\Attribute\InList;
use Docile\Validation\Attribute\IntType;
use Docile\Validation\Attribute\Length;
use Docile\Validation\Attribute\NotBlank;
use Docile\Validation\Attribute\Range;
use Docile\Validation\Attribute\Regex;
use Docile\Validation\Attribute\Rule as RuleAttribute;
use Docile\Validation\Attribute\Required;
use Docile\Validation\Attribute\StringType;
use Docile\Validation\Attribute\BoolType;
use Docile\Validation\Exception\ValidationException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

final readonly class Validator
{
    /**
     * Validate an array of data against a DTO class (reads property attributes)
     * Returns a populated DTO on success, throws ValidationException on failure
     *
     * @param array<string, mixed> $data
     * @param class-string $dtoClass
     * @throws ValidationException
     * @throws ReflectionException
     */
    public function validate(array $data, string $dtoClass): object
    {
        $violations = new ViolationList();
        $reflection = new ReflectionClass($dtoClass);
        $properties = $reflection->getProperties();

        $constructorArgs = [];
        $propertiesToSet = [];

        foreach ($properties as $property) {
            $fieldName = $property->getName();
            $value = $data[$fieldName] ?? null;
            $attributes = $property->getAttributes();

            // Check if field is required but missing
            foreach ($attributes as $attr) {
                $attribute = $attr->newInstance();
                if ($attribute instanceof Required && !array_key_exists($fieldName, $data)) {
                    $violations->add($fieldName, 'This field is required.');
                    continue 2;
                }
            }

            // Skip validation if field is not provided and not required
            if (!array_key_exists($fieldName, $data)) {
                continue;
            }

            // Validate each attribute
            foreach ($attributes as $attr) {
                $attribute = $attr->newInstance();
                $error = $this->validateAttribute($attribute, $value, $fieldName, $data);
                if ($error !== null) {
                    $violations->add($fieldName, $error);
                }
            }

            // If no violations, prepare for DTO population
            if (!$violations->has($fieldName)) {
                // Check if property is constructor promoted
                $constructor = $reflection->getConstructor();
                $isPromoted = false;
                if ($constructor !== null) {
                    foreach ($constructor->getParameters() as $param) {
                        if ($param->getName() === $fieldName) {
                            $isPromoted = true;
                            break;
                        }
                    }
                }
                
                if ($isPromoted) {
                    $constructorArgs[$fieldName] = $value;
                } else {
                    $propertiesToSet[$fieldName] = $value;
                }
            }
        }

        if (!$violations->isEmpty()) {
            throw new ValidationException($violations);
        }

        // Create DTO instance
        if ($constructorArgs !== []) {
            $dto = $reflection->newInstanceArgs($constructorArgs);
        } else {
            $dto = $reflection->newInstanceWithoutConstructor();
        }

        // Set non-constructor properties
        foreach ($propertiesToSet as $fieldName => $value) {
            $property = $reflection->getProperty($fieldName);
            $property->setValue($dto, $value);
        }

        return $dto;
    }

    /**
     * Validate just a plain array without a DTO — returns ViolationList
     *
     * @param array<string, mixed> $data
     * @param array<string, array<object>> $rules
     */
    public function validateArray(array $data, array $rules): ViolationList
    {
        $violations = new ViolationList();

        foreach ($rules as $field => $attributes) {
            $value = $data[$field] ?? null;

            // Check if field is required but missing
            foreach ($attributes as $attribute) {
                if ($attribute instanceof Required && !array_key_exists($field, $data)) {
                    $violations->add($field, 'This field is required.');
                    continue 2;
                }
            }

            // Skip validation if field is not provided and not required
            if (!array_key_exists($field, $data)) {
                continue;
            }

            // Validate each attribute
            foreach ($attributes as $attribute) {
                $error = $this->validateAttribute($attribute, $value, $field, $data);
                if ($error !== null) {
                    $violations->add($field, $error);
                }
            }
        }

        return $violations;
    }

    /** @param array<string, mixed> $data */
    private function validateAttribute(object $attribute, mixed $value, string $field, array $data): ?string
    {
        return match ($attribute::class) {
            Required::class => null, // Handled before this point
            NotBlank::class => $this->validateNotBlank($value, $attribute->message),
            StringType::class => $this->validateStringType($value),
            IntType::class => $this->validateIntType($value),
            FloatType::class => $this->validateFloatType($value),
            BoolType::class => $this->validateBoolType($value),
            Email::class => $this->validateEmail($value, $attribute->message),
            Length::class => $this->validateLength($value, $attribute->min, $attribute->max, $attribute->message),
            Range::class => $this->validateRange($value, $attribute->min, $attribute->max, $attribute->message),
            Regex::class => $this->validateRegex($value, $attribute->pattern, $attribute->message),
            InList::class => $this->validateInList($value, $attribute->choices, $attribute->message),
            Confirmed::class => $this->validateConfirmed($value, $field, $data, $attribute->message),
            RuleAttribute::class => $this->validateCustomRule($value, $field, $attribute->ruleClass),
            default => null,
        };
    }

    private function validateNotBlank(mixed $value, string $message): ?string
    {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return $message;
        }
        return null;
    }

    private function validateStringType(mixed $value): ?string
    {
        if ($value !== null && !is_string($value)) {
            return 'Value must be a string.';
        }
        return null;
    }

    private function validateIntType(mixed $value): ?string
    {
        if ($value !== null && !is_int($value)) {
            return 'Value must be an integer.';
        }
        return null;
    }

    private function validateFloatType(mixed $value): ?string
    {
        if ($value !== null && !is_float($value)) {
            return 'Value must be a float.';
        }
        return null;
    }

    private function validateBoolType(mixed $value): ?string
    {
        if ($value !== null && !is_bool($value)) {
            return 'Value must be a boolean.';
        }
        return null;
    }

    private function validateEmail(mixed $value, string $message): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return $message;
        }
        return null;
    }

    private function validateLength(mixed $value, ?int $min, ?int $max, string $message): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return 'Value must be a string for length validation.';
        }

        $length = strlen($value);

        if ($min !== null && $length < $min) {
            return $message;
        }

        if ($max !== null && $length > $max) {
            return $message;
        }

        return null;
    }

    private function validateRange(mixed $value, int|float|null $min, int|float|null $max, string $message): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_int($value) && !is_float($value)) {
            return 'Value must be numeric for range validation.';
        }

        if ($min !== null && $value < $min) {
            return $message;
        }

        if ($max !== null && $value > $max) {
            return $message;
        }

        return null;
    }

    private function validateRegex(mixed $value, string $pattern, string $message): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return 'Value must be a string for regex validation.';
        }

        if (preg_match($pattern, $value) === 0) {
            return $message;
        }

        return null;
    }

    /** @param array<mixed> $choices */
    private function validateInList(mixed $value, array $choices, string $message): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!in_array($value, $choices, true)) {
            return $message;
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private function validateConfirmed(mixed $value, string $field, array $data, string $message): ?string
    {
        $confirmationField = $field . 'Confirmation';
        
        if (!array_key_exists($confirmationField, $data)) {
            return null; // Confirmation field not provided, skip this validation
        }

        $confirmationValue = $data[$confirmationField];

        if ($value !== $confirmationValue) {
            return $message;
        }

        return null;
    }

    private function validateCustomRule(mixed $value, string $field, string $ruleClass): ?string
    {
        if (!class_exists($ruleClass)) {
            return sprintf('Custom rule class %s does not exist.', $ruleClass);
        }

        if (!is_subclass_of($ruleClass, RuleInterface::class)) {
            return sprintf('Custom rule class %s must implement %s.', $ruleClass, RuleInterface::class);
        }

        /** @var RuleInterface $rule */
        $rule = new $ruleClass();
        return $rule->validate($value, $field);
    }
}