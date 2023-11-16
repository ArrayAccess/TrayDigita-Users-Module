<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Traits;

use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Exceptions\Runtime\RuntimeException;
use function sprintf;

trait UserModuleAssertionTrait
{
    private function assertObjectUser(): void
    {
        if (!$this instanceof Users) {
            throw new RuntimeException(
                sprintf(
                    'Object trait should instance of : %s',
                    Users::class
                )
            );
        }
    }
}
