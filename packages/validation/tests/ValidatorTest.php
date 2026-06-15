<?php

declare(strict_types=1);

namespace Docile\Validation\Tests;

use Docile\Validation\Attribute\BoolType;
use Docile\Validation\Attribute\Confirmed;
use Docile\Validation\Attribute\Email;
use Docile\Validation\Attribute\FloatType;
use Docile\Validation\Attribute\InList;
use Docile\Validation\Attribute\IntType;
use Docile\Validation\Attribute\Length;
use Docile\Validation\Attribute\Range;
use Docile\Validation\Attribute\Regex;
use Docile\Validation\Attribute\Required;
use Docile\Validation\Attribute\Rule as RuleAttribute;
use Docile\Validation\Attribute\StringType;
use Docile\Validation\Exception\ValidationException;
use Docile\Validation\Tests\Fixtures\AddressDto;
use Docile\Validation\Tests\Fixtures\CreateUserDto;
use Docile\Validation\Tests\Fixtures\CustomRule;
use Docile\Validation\Validator;
use Docile\Validation\ViolationList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Validator::class)]
final class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidDataPassesAndPopulatesDto(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'bio' => 'A software developer',
        ];

        $dto = $this->validator->validate($data, CreateUserDto::class);

        $this->assertInstanceOf(CreateUserDto::class, $dto);
        $this->assertSame('John Doe', $dto->name);
        $this->assertSame('john@example.com', $dto->email);
        $this->assertSame('password123', $dto->password);
        $this->assertSame('A software developer', $dto->bio);
    }

    public function testMissingRequiredFieldThrowsException(): void
    {
        $data = [
            'name' => 'John Doe',
            // email is missing
            'password' => 'password123',
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed.');

        try {
            $this->validator->validate($data, CreateUserDto::class);
        } catch (ValidationException $e) {
            $violations = $e->violations();
            $this->assertTrue($violations->has('email'));
            $this->assertSame(['This field is required.'], $violations->get('email'));
            throw $e;
        }
    }

    public function testInvalidEmail(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->validator->validate($data, CreateUserDto::class);
        } catch (ValidationException $e) {
            $violations = $e->violations();
            $this->assertTrue($violations->has('email'));
            $this->assertSame(['Invalid email address.'], $violations->get('email'));
            throw $e;
        }
    }

    public function testLengthConstraint(): void
    {
        // Test minimum length
        $data = [
            'name' => 'J', // Too short
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->validator->validate($data, CreateUserDto::class);
        } catch (ValidationException $e) {
            $violations = $e->violations();
            $this->assertTrue($violations->has('name'));
            $this->assertSame(['Length out of range.'], $violations->get('name'));
            throw $e;
        }
    }

    public function testRangeConstraint(): void
    {
        $data = [
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zipCode' => '12345',
            'country' => 'US',
            'apartmentNumber' => 0, // Below minimum range
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->validator->validate($data, AddressDto::class);
        } catch (ValidationException $e) {
            $violations = $e->violations();
            $this->assertTrue($violations->has('apartmentNumber'));
            $this->assertSame(['Value out of range.'], $violations->get('apartmentNumber'));
            throw $e;
        }
    }

    public function testRegexConstraint(): void
    {
        $data = [
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'California', // Should be 2 letters
            'zipCode' => '12345',
            'country' => 'US',
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->validator->validate($data, AddressDto::class);
        } catch (ValidationException $e) {
            $violations = $e->violations();
            $this->assertTrue($violations->has('state'));
            $this->assertSame(['State must be a 2-letter uppercase code.'], $violations->get('state'));
            throw $e;
        }
    }

    public function testInListConstraint(): void
    {
        $data = [
            'street' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'zipCode' => '12345',
            'country' => 'UK', // Not in allowed list
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->validator->validate($data, AddressDto::class);
        } catch (ValidationException $e) {
            $violations = $e->violations();
            $this->assertTrue($violations->has('country'));
            $this->assertSame(['Country must be US, CA, or MX.'], $violations->get('country'));
            throw $e;
        }
    }

    public function testConfirmedConstraint(): void
    {
        // Create a test DTO with confirmation
        $anonymousClass = new class('') {
            public function __construct(
                #[Required]
                #[Confirmed]
                public string $password,
            ) {}
        };
        $testDto = get_class($anonymousClass);

        // Test non-matching confirmation
        $data = [
            'password' => 'secret',
            'passwordConfirmation' => 'different',
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->validator->validate($data, $testDto);
        } catch (ValidationException $e) {
            $violations = $e->violations();
            $this->assertTrue($violations->has('password'));
            $this->assertSame(['Confirmation does not match.'], $violations->get('password'));
            throw $e;
        }
    }

    public function testConfirmedConstraintWithMatchingValues(): void
    {
        $anonymousClass = new class('') {
            public function __construct(
                #[Required]
                #[Confirmed]
                public string $password,
            ) {}
        };
        $testDto = get_class($anonymousClass);

        $data = [
            'password' => 'secret',
            'passwordConfirmation' => 'secret',
        ];

        $dto = $this->validator->validate($data, $testDto);
        $this->assertSame('secret', $dto->password);
    }

    public function testCustomRule(): void
    {
        $anonymousClass = new class('') {
            public function __construct(
                #[Required]
                #[StringType]
                #[RuleAttribute(CustomRule::class)]
                public string $content,
            ) {}
        };
        $testDto = get_class($anonymousClass);

        $data = [
            'content' => 'This contains forbidden word',
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->validator->validate($data, $testDto);
        } catch (ValidationException $e) {
            $violations = $e->violations();
            $this->assertTrue($violations->has('content'));
            $this->assertSame(["Value cannot contain the word 'forbidden'."], $violations->get('content'));
            throw $e;
        }
    }

    public function testValidateArray(): void
    {
        $rules = [
            'email' => [new Required(), new Email()],
            'age' => [new Required(), new IntType(), new Range(min: 18, max: 120)],
        ];

        // Valid data
        $data = [
            'email' => 'test@example.com',
            'age' => 25,
        ];

        $violations = $this->validator->validateArray($data, $rules);
        $this->assertTrue($violations->isEmpty());

        // Invalid data
        $invalidData = [
            'email' => 'invalid-email',
            'age' => 15,
        ];

        $violations = $this->validator->validateArray($invalidData, $rules);
        $this->assertFalse($violations->isEmpty());
        $this->assertTrue($violations->has('email'));
        $this->assertTrue($violations->has('age'));
    }

    public function testTypeValidation(): void
    {
        $anonymousClass = new class('', 0, 0.0, false) {
            public function __construct(
                #[Required]
                #[StringType]
                public string $stringField,
                
                #[Required]
                #[IntType]
                public int $intField,
                
                #[Required]
                #[FloatType]
                public float $floatField,
                
                #[Required]
                #[BoolType]
                public bool $boolField,
            ) {}
        };
        $testDto = get_class($anonymousClass);

        $data = [
            'stringField' => 123, // Should be string
            'intField' => 'not-an-int', // Should be int
            'floatField' => 'not-a-float', // Should be float
            'boolField' => 'not-a-bool', // Should be bool
        ];

        $this->expectException(ValidationException::class);

        try {
            $this->validator->validate($data, $testDto);
        } catch (ValidationException $e) {
            $violations = $e->violations();
            $this->assertTrue($violations->has('stringField'));
            $this->assertTrue($violations->has('intField'));
            $this->assertTrue($violations->has('floatField'));
            $this->assertTrue($violations->has('boolField'));
            $this->assertSame(['Value must be a string.'], $violations->get('stringField'));
            $this->assertSame(['Value must be an integer.'], $violations->get('intField'));
            $this->assertSame(['Value must be a float.'], $violations->get('floatField'));
            $this->assertSame(['Value must be a boolean.'], $violations->get('boolField'));
            throw $e;
        }
    }
}