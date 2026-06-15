<?php

declare(strict_types=1);

namespace Docile\Validation\Tests;

use Docile\Validation\Attribute\Confirmed;
use Docile\Validation\Attribute\Email;
use Docile\Validation\Attribute\FloatType;
use Docile\Validation\Attribute\InList;
use Docile\Validation\Attribute\IntType;
use Docile\Validation\Attribute\Length;
use Docile\Validation\Attribute\NotBlank;
use Docile\Validation\Attribute\Range;
use Docile\Validation\Attribute\Regex;
use Docile\Validation\Attribute\Required;
use Docile\Validation\Attribute\Rule as RuleAttribute;
use Docile\Validation\Attribute\StringType;
use Docile\Validation\Attribute\BoolType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Required::class)]
#[CoversClass(NotBlank::class)]
#[CoversClass(StringType::class)]
#[CoversClass(IntType::class)]
#[CoversClass(FloatType::class)]
#[CoversClass(BoolType::class)]
#[CoversClass(Email::class)]
#[CoversClass(Length::class)]
#[CoversClass(Range::class)]
#[CoversClass(Regex::class)]
#[CoversClass(InList::class)]
#[CoversClass(Confirmed::class)]
#[CoversClass(RuleAttribute::class)]
final class AttributeTest extends TestCase
{
    public function testRequiredAttribute(): void
    {
        $attribute = new Required();
        $reflection = new ReflectionClass($attribute);
        
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testNotBlankAttribute(): void
    {
        $defaultMessage = 'Must not be blank.';
        $customMessage = 'This field cannot be empty.';
        
        $defaultAttribute = new NotBlank();
        $customAttribute = new NotBlank($customMessage);
        
        $this->assertSame($defaultMessage, $defaultAttribute->message);
        $this->assertSame($customMessage, $customAttribute->message);
        
        $reflection = new ReflectionClass($defaultAttribute);
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testStringTypeAttribute(): void
    {
        $attribute = new StringType();
        $reflection = new ReflectionClass($attribute);
        
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testIntTypeAttribute(): void
    {
        $attribute = new IntType();
        $reflection = new ReflectionClass($attribute);
        
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testFloatTypeAttribute(): void
    {
        $attribute = new FloatType();
        $reflection = new ReflectionClass($attribute);
        
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testBoolTypeAttribute(): void
    {
        $attribute = new BoolType();
        $reflection = new ReflectionClass($attribute);
        
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testEmailAttribute(): void
    {
        $defaultMessage = 'Invalid email address.';
        $customMessage = 'Please enter a valid email.';
        
        $defaultAttribute = new Email();
        $customAttribute = new Email($customMessage);
        
        $this->assertSame($defaultMessage, $defaultAttribute->message);
        $this->assertSame($customMessage, $customAttribute->message);
        
        $reflection = new ReflectionClass($defaultAttribute);
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testLengthAttribute(): void
    {
        $defaultMessage = 'Length out of range.';
        $customMessage = 'Invalid length.';
        
        $defaultAttribute = new Length();
        $minOnlyAttribute = new Length(min: 5);
        $maxOnlyAttribute = new Length(max: 10);
        $bothAttribute = new Length(min: 5, max: 10);
        $customMessageAttribute = new Length(min: 5, max: 10, message: $customMessage);
        
        $this->assertNull($defaultAttribute->min);
        $this->assertNull($defaultAttribute->max);
        $this->assertSame($defaultMessage, $defaultAttribute->message);
        
        $this->assertSame(5, $minOnlyAttribute->min);
        $this->assertNull($minOnlyAttribute->max);
        
        $this->assertNull($maxOnlyAttribute->min);
        $this->assertSame(10, $maxOnlyAttribute->max);
        
        $this->assertSame(5, $bothAttribute->min);
        $this->assertSame(10, $bothAttribute->max);
        
        $this->assertSame($customMessage, $customMessageAttribute->message);
        
        $reflection = new ReflectionClass($defaultAttribute);
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testRangeAttribute(): void
    {
        $defaultMessage = 'Value out of range.';
        $customMessage = 'Invalid value.';
        
        $defaultAttribute = new Range();
        $minOnlyAttribute = new Range(min: 5);
        $maxOnlyAttribute = new Range(max: 10.5);
        $bothAttribute = new Range(min: 5, max: 10);
        $floatRangeAttribute = new Range(min: 1.5, max: 10.5);
        $customMessageAttribute = new Range(min: 5, max: 10, message: $customMessage);
        
        $this->assertNull($defaultAttribute->min);
        $this->assertNull($defaultAttribute->max);
        $this->assertSame($defaultMessage, $defaultAttribute->message);
        
        $this->assertSame(5, $minOnlyAttribute->min);
        $this->assertNull($minOnlyAttribute->max);
        
        $this->assertNull($maxOnlyAttribute->min);
        $this->assertSame(10.5, $maxOnlyAttribute->max);
        
        $this->assertSame(5, $bothAttribute->min);
        $this->assertSame(10, $bothAttribute->max);
        
        $this->assertSame(1.5, $floatRangeAttribute->min);
        $this->assertSame(10.5, $floatRangeAttribute->max);
        
        $this->assertSame($customMessage, $customMessageAttribute->message);
        
        $reflection = new ReflectionClass($defaultAttribute);
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testRegexAttribute(): void
    {
        $defaultMessage = 'Value does not match pattern.';
        $customMessage = 'Invalid format.';
        $pattern = '/^[A-Z]+$/';
        
        $defaultAttribute = new Regex($pattern);
        $customMessageAttribute = new Regex($pattern, $customMessage);
        
        $this->assertSame($pattern, $defaultAttribute->pattern);
        $this->assertSame($defaultMessage, $defaultAttribute->message);
        
        $this->assertSame($pattern, $customMessageAttribute->pattern);
        $this->assertSame($customMessage, $customMessageAttribute->message);
        
        $reflection = new ReflectionClass($defaultAttribute);
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testInListAttribute(): void
    {
        $defaultMessage = 'Value not in allowed list.';
        $customMessage = 'Invalid choice.';
        $choices = ['red', 'green', 'blue'];
        
        $defaultAttribute = new InList($choices);
        $customMessageAttribute = new InList($choices, $customMessage);
        
        $this->assertSame($choices, $defaultAttribute->choices);
        $this->assertSame($defaultMessage, $defaultAttribute->message);
        
        $this->assertSame($choices, $customMessageAttribute->choices);
        $this->assertSame($customMessage, $customMessageAttribute->message);
        
        $reflection = new ReflectionClass($defaultAttribute);
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testConfirmedAttribute(): void
    {
        $defaultMessage = 'Confirmation does not match.';
        $customMessage = 'Passwords do not match.';
        
        $defaultAttribute = new Confirmed();
        $customAttribute = new Confirmed($customMessage);
        
        $this->assertSame($defaultMessage, $defaultAttribute->message);
        $this->assertSame($customMessage, $customAttribute->message);
        
        $reflection = new ReflectionClass($defaultAttribute);
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }

    public function testRuleAttribute(): void
    {
        $ruleClass = 'Test\\RuleClass';
        
        $attribute = new RuleAttribute($ruleClass);
        
        $this->assertSame($ruleClass, $attribute->ruleClass);
        
        $reflection = new ReflectionClass($attribute);
        $this->assertTrue($reflection->isReadOnly());
        $this->assertTrue($reflection->isFinal());
    }
}