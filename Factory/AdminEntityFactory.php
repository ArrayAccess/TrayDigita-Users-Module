<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Factory;

use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Admin;
use ArrayAccess\TrayDigita\Auth\Cookie\Interfaces\UserEntityFactoryInterface;
use ArrayAccess\TrayDigita\Database\Connection;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\UserEntityInterface;

class AdminEntityFactory implements UserEntityFactoryInterface
{
    public function __construct(protected Connection $connection)
    {
    }

    public function findById(int $id): ?UserEntityInterface
    {
        return $this->connection->find(
            Admin::class,
            $id
        );
    }

    public function findByUsername(string $username) : ?UserEntityInterface
    {
        return $this->connection->findOneBy(
            Admin::class,
            ['username' => $username]
        );
    }
}
