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
        'comment' => 'User term metadata',
        'primaryKey' => [
            'term_id',
            'term_group_id'
        ]
    ]
)]
#[Index(
    name: 'index_name',
    columns: ['name']
)]
#[Index(
    name: 'relation_user_term_metadata_term_id_user_terms_id',
    columns: ['term_id']
)]
#[HasLifecycleCallbacks]
/**
 * @property-read int $term_id
 * @property-read UserTerm $userTerm
 */
class UserTermMeta extends AbstractBasedMeta
{
    public const TABLE_NAME = 'user_term_meta';

    #[Id]
    #[Column(
        name: 'term_id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key composite identifier'
        ]
    )]
    protected int $term_id;

    #[
        JoinColumn(
            name: 'term_id',
            referencedColumnName: 'id',
            nullable: false,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_user_term_metadata_term_id_user_terms_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: UserTerm::class,
            cascade: [
                "persist",
                "remove",
                // "merge",
                "detach"
            ],
            fetch: 'EAGER'
        )
    ]
    protected UserTerm $userTerm;

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    public function getTermId(): int
    {
        return $this->term_id;
    }

    public function setTermId(int $term_id): void
    {
        $this->term_id = $term_id;
    }

    public function getUserTerm(): UserTerm
    {
        return $this->userTerm;
    }

    public function setUserTerm(UserTerm $userTerm): void
    {
        $this->userTerm = $userTerm;
        $this->setTermId($userTerm->getId());
    }
}
