<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes;

use ArrayAccess\TrayDigita\Kernel\Decorator;
use Attribute;
use function is_string;

#[Attribute]
abstract class AbstractAPIAttributes extends RouteAPI
{
    public static function subPrefix(): string
    {
        $manager = Decorator::manager();
        $subPrefixOriginal = trim(static::API_SUB_PREFIX, '/');
        $subPrefix = $manager->dispatch(
            'apiRoute.subPrefix',
            $subPrefixOriginal,
            static::class
        );
        return trim(is_string($subPrefix) ? $subPrefix : $subPrefixOriginal, '/');
    }
}
