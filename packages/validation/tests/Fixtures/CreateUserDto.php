<?php

declare(strict_types=1);

namespace Docile\Validation\Tests\Fixtures;

use Docile\Validation\Attribute\Email;
use Docile\Validation\Attribute\Length;
use Docile\Validation\Attribute\Required;
use Docile\Validation\Attribute\StringType;

final readonly class CreateUserDto
{
    public function __construct(
        #[Required]
        #[StringType]
        #[Length(min: 2, max: 50)]
        public string $name,
        
        #[Required]
        #[Email]
        public string $email,
        
        #[Required]
        #[StringType]
        #[Length(min: 8)]
        public string $password,
        
        #[StringType]
        public ?string $bio = null,
    ) {}
}