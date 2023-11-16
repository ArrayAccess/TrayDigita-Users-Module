<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class User extends Dashboard
{
    protected static string $prefix = 'user';
}
