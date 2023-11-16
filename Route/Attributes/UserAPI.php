<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class UserAPI extends AbstractAPIAttributes
{
    const API_SUB_PREFIX = 'user';
}
