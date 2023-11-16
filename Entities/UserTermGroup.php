<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\AvailabilityStatusEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Traits\AvailabilityStatusTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Group terms user collection',
    ]
)]
#[UniqueConstraint(
    name: 'unique_user_id_term_id',
    columns: ['user_id', 'term_id']
)]
#[Index(
    columns: ['term_id'],
    name: 'relation_user_term_groups_term_id_user_terms_id'
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_user_term_groups_user_id_users_id'
)]
#[HasLifecycleCallbacks]
/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read int $term_id
 * @property-read string $status
 * @property-read UserTerm $term
 */
class UserTermGroup extends AbstractEntity implements AvailabilityStatusEntityInterface
{
    public const TABLE_NAME = 'user_term_groups';

    use AvailabilityStatusTrait;

    #[Id]
    #[GeneratedValue('AUTO')]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key term group id'
        ]
    )]
    protected int $id;

    #[Column(
        name: 'user_id',
        type: Types::BIGINT,
        length: 20,
        nullable: false,
        options:  [
            'unsigned' => true,
            'comment' => 'User id'
        ]
    )]
    protected int $user_id;

    #[Column(
        name: 'term_id',
        type: Types::BIGINT,
        length: 20,
        nullable: false,
        options:  [
            'unsigned' => true,
            'comment' => 'User term id'
        ]
    )]
    protected int $term_id;

    #[Column(
        name: 'status',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: [
            'comment' => 'Term group status'
        ]
    )]
    protected string $status;
    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_user_term_groups_user_id_users_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ],
        ),
        ManyToOne(
            targetEntity: User::class,
            cascade: [
                'persist'
            ],
            fetch: 'EAGER'
        )
    ]
    protected User $user;

    #[
        JoinColumn(
            name: 'term_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_user_term_groups_term_id_user_terms_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ],
        ),
        ManyToOne(
            targetEntity: UserTerm::class,
            cascade: [
                'persist'
            ],
            fetch: 'EAGER'
        )
    ]
    protected UserTerm $term;

    public function __construct()
    {
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getTermId(): int
    {
        return $this->term_id;
    }

    public function setTermId(?int $term_id): void
    {
        $this->term_id = $term_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->setUserId($user->getId());
    }

    public function getTerm(): UserTerm
    {
        return $this->term;
    }

    public function setTerm(UserTerm $term): void
    {
        $this->term = $term;
        $this->setTermId($term->getId());
    }
}
