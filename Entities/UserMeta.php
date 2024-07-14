<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractBasedMeta;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
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
        'comment' => 'Common user metadata',
        'primaryKey' => [
            'user_id',
            'name'
        ]
    ]
)]
#[Index(
    name: 'index_name',
    columns: ['name']
)]
#[Index(
    name: 'relation_user_meta_user_id_users_id',
    columns: ['user_id']
)]
#[HasLifecycleCallbacks]
/**
 * @property-read int $user_id
 * @property-read User $user
 */
class UserMeta extends AbstractBasedMeta
{
    public const TABLE_NAME = 'user_meta';

    #[Id]
    #[Column(
        name: 'user_id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key composite identifier'
        ]
    )]
    protected int $user_id;

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_user_meta_user_id_users_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: User::class,
            cascade: [
                "persist",
                "remove",
                // "merge",
                "detach"
            ],
            fetch: 'EAGER'
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

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

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
