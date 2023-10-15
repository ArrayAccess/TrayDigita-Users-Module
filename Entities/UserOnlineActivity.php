<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractBasedOnlineActivity;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Common user online activity',
    ]
)]
#[Index(
    columns: ['user_id', 'name', 'created_at', 'updated_at'],
    name: 'index_user_id_name_created_at_updated_at'
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_user_online_activities_user_id_users_id'
)]
class UserOnlineActivity extends AbstractBasedOnlineActivity
{
    const TABLE_NAME = 'user_online_activities';

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_user_online_activities_user_id_users_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        OneToOne(
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
