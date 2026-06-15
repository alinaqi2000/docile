<?php

declare(strict_types=1);

namespace Docile\Validation\Tests\Fixtures;

use Docile\Validation\Attribute\InList;
use Docile\Validation\Attribute\Length;
use Docile\Validation\Attribute\Range;
use Docile\Validation\Attribute\Regex;
use Docile\Validation\Attribute\Required;
use Docile\Validation\Attribute\StringType;

final readonly class AddressDto
{
    public function __construct(
        #[Required]
        #[StringType]
        #[Length(min: 5, max: 100)]
        public string $street,
        
        #[Required]
        #[StringType]
        #[Length(min: 2, max: 50)]
        public string $city,
        
        #[Required]
        #[StringType]
        #[Regex(pattern: '/^[A-Z]{2}$/', message: 'State must be a 2-letter uppercase code.')]
        public string $state,
        
        #[Required]
        #[StringType]
        #[Regex(pattern: '/^\d{5}(-\d{4})?$/', message: 'Invalid ZIP code format.')]
        public string $zipCode,
        
        #[Required]
        #[InList(choices: ['US', 'CA', 'MX'], message: 'Country must be US, CA, or MX.')]
        public string $country,
        
        #[Range(min: 1, max: 99999)]
        public ?int $apartmentNumber = null,
    ) {}
}