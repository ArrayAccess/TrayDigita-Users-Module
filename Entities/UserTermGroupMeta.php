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
        'comment' => 'User term group metadata',
        'primaryKey' => [
            'term_group_id',
            'name'
        ]
    ]
)]
#[Index(
    columns: ['name'],
    name: 'index_name'
)]
#[Index(
    columns: ['term_group_id'],
    name: 'relation_user_term_group_meta_term_group_id_user_terms_group_id'
)]
#[HasLifecycleCallbacks]
/**
 * @property-read int $term_group_id
 * @property-read UserTermGroup $userTermGroup
 */
class UserTermGroupMeta extends AbstractBasedMeta
{
    public const TABLE_NAME = 'user_term_group_meta';

    #[Id]
    #[Column(
        name: 'term_group_id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key composite identifier'
        ]
    )]
    protected int $term_group_id;

    #[
        JoinColumn(
            name: 'term_group_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_user_term_group_meta_term_group_id_user_terms_group_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: UserTermGroup::class,
            cascade: [
                "persist",
                "remove",
                "merge",
                "detach"
            ],
            fetch: 'EAGER'
        )
    ]
    protected UserTermGroup $userTermGroup;

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    public function getTermGroupId(): int
    {
        return $this->term_group_id;
    }

    public function setTermGroupId(int $term_group_id): void
    {
        $this->term_group_id = $term_group_id;
    }

    public function getUserTermGroup(): UserTermGroup
    {
        return $this->userTermGroup;
    }

    public function setUserTermGroup(UserTermGroup $userTermGroup): void
    {
        $this->userTermGroup = $userTermGroup;
        $this->setTermGroupId($userTermGroup->getId());
    }
}
