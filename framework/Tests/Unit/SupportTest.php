<?php


namespace Docile\Tests\Unit;

use Docile\Core\Router;
use Docile\Support\Str;
use PHPUnit\Framework\TestCase;

class SupportTest extends TestCase
{

    public function testStrforSnakeToTitleCase(): void
    {
        $strings = [
            ['query' => "user_name", "result" => "UserName"],
            ['query' => "phone_number_for_user", "result" => "PhoneNumberForUser"],
        ];

        foreach ($strings as $str) {
            $this->assertSame($str['result'], Str::convertSnakeToTitleCase($str['query']));
        }
    }
    public function testStrforPlural(): void
    {
        $strings = [
            ['query' => "baby", "result" => "babies"],
            ['query' => "user", "result" => "users"],
            ['query' => "key", "result" => "keys"],
            ['query' => "child", "result" => "children"],
        ];

        foreach ($strings as $str) {
            $this->assertSame($str['result'], Str::plural($str['query']));
        }
    }
}
