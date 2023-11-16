<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Depends;

use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

abstract class AbstractRepositoryUserDepends
{
    public function __construct(public Users $users)
    {
    }

    /**
     * @return ObjectRepository&Selectable
     */
    abstract public function getRepository() : ObjectRepository&Selectable;
}
