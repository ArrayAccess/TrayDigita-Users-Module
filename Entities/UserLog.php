<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractUserBasedLog;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Common user logs',
    ],
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_user_logs_user_id_users_id'
)]
#[Index(
    columns: ['user_id', 'name', 'type'],
    name: 'index_user_id_name_type'
)]
#[HasLifecycleCallbacks]
class UserLog extends AbstractUserBasedLog
{
    public const TABLE_NAME = 'user_logs';

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_user_logs_user_id_users_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: User::class,
            cascade: [
                "persist",
                "remove",
                "merge",
                "detach"
            ],
            fetch: 'LAZY'
        )
    ]
    protected User $user;

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->setUserId($user->getId());
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
