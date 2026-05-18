<?php

declare(strict_types=1);

namespace Unquam\NetteMaker\Tests\Support;

use PHPUnit\Framework\TestCase;
use Unquam\NetteMaker\Support\Inflector;

class InflectorTest extends TestCase
{
    /**
     * @dataProvider classNameProvider
     */
    public function testToClassName(string $input, string $expected): void
    {
        $this->assertSame($expected, Inflector::toClassName($input));
    }

    /**
     * @dataProvider tableNameProvider
     */
    public function testToTableName(string $input, string $expected): void
    {
        $this->assertSame($expected, Inflector::toTableName($input));
    }

    public static function classNameProvider(): array
    {
        return [
            ['users', 'User'],
            ['user_profiles', 'UserProfile'],
            ['user-profiles', 'UserProfile'],
            ['UserProfile', 'UserProfile'],
            ['blog_post', 'BlogPost'],
            ['BlogPosts', 'BlogPost'],
        ];
    }

    public static function tableNameProvider(): array
    {
        return [
            ['User', 'users'],
            ['UserProfile', 'user_profiles'],
            ['user_profile', 'user_profiles'],
            ['BlogPost', 'blog_posts'],
            ['blog_post', 'blog_posts'],
        ];
    }
}