<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\AvailabilityStatusEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Traits\AvailabilityStatusTrait;
use ArrayAccess\TrayDigita\Database\Entities\Traits\PasswordTrait;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'User terms grouping metadata like quiz sorting',
    ]
)]
#[UniqueConstraint(
    name: 'unique_name_site_id',
    columns: ['name', 'site_id']
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_user_terms_user_id_admins_id'
)]
#[Index(
    columns: ['site_id'],
    name: 'relation_user_terms_site_id_sites_id'
)]
#[Index(
    columns: ['name', 'title', 'status'],
    name: 'index_name_title_status'
)]
#[HasLifecycleCallbacks]
/**
 * @property-read int $id
 * @property-read ?int $user_id
 * @property-read string $name
 * @property-read ?string $title
 * @property-read string $status
 * @property-read ?string $password
 * @property-read ?DateTimeInterface $published_at
 * @property-read DateTimeInterface $created_at
 * @property-read DateTimeInterface $updated_at
 * @property-read ?DateTimeInterface $deleted_at
 * @property-read ?Site $site
 */
class UserTerm extends AbstractEntity implements AvailabilityStatusEntityInterface
{
    public const TABLE_NAME = 'user_terms';

    use AvailabilityStatusTrait,
        PasswordTrait;

    #[Id]
    #[GeneratedValue('AUTO')]
    #[Column(
        name: 'id',
        type: Types::BIGINT,
        length: 20,
        updatable: false,
        options: [
            'unsigned' => true,
            'comment' => 'Primary key'
        ]
    )]
    protected int $id;
    #[Column(
        name: 'user_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Admin id'
        ]
    )]
    protected ?int $user_id = null;

    #[Column(
        name: 'site_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options:  [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Admin id'
        ]
    )]
    protected ?int $site_id = null;

    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        unique: true,
        nullable: false,
        options: [
            'comment' => 'Unique name'
        ]
    )]
    protected string $name;

    #[Column(
        name: 'title',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Term title'
        ]
    )]
    protected ?string $title;

    #[Column(
        name: 'content',
        type: Types::TEXT,
        length: 4294967295,
        nullable: true,
        options:  [
            'default' => null,
            'comment' => 'Term content'
        ]
    )]
    protected ?string $content = null;

    #[Column(
        name: 'status',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: [
            'default' => self::DRAFT,
            'comment' => 'Term status'
        ]
    )]
    protected string $status = self::DRAFT;

    #[Column(
        name: 'password',
        type: Types::STRING,
        length: 255,
        nullable: true,
        updatable: true,
        options: [
            'default' => null,
            'comment' => 'Term password'
        ]
    )]
    protected ?string $password = null;

    #[Column(
        name: 'published_at',
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Published at'
        ]
    )]
    protected ?DateTimeInterface $published_at = null;

    #[Column(
        name: 'created_at',
        type: Types::DATETIME_MUTABLE,
        updatable: false,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'User term created time'
        ]
    )]
    protected DateTimeInterface $created_at;

    #[Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE,
        unique: false,
        updatable: false,
        options: [
            'attribute' => 'ON UPDATE CURRENT_TIMESTAMP', // this column attribute
            'default' => '0000-00-00 00:00:00',
            'comment' => 'User term update time'
        ],
        // columnDefinition: "DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP"
    )]
    protected DateTimeInterface $updated_at;

    #[Column(
        name: 'deleted_at',
        type: Types::DATETIME_IMMUTABLE,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'User term delete time'
        ]
    )]
    protected ?DateTimeInterface $deleted_at = null;

    #[
        JoinColumn(
            name: 'site_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_user_terms_site_id_sites_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: Site::class,
            cascade: [
                "persist"
            ],
            fetch: 'EAGER'
        )
    ]
    protected ?Site $site = null;

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_user_terms_user_id_admins_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: Admin::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?Admin $user = null;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable('0000-00-00 00:00:00');
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getPublishedAt(): ?DateTimeInterface
    {
        return $this->published_at;
    }

    public function setPublishedAt(?DateTimeInterface $published_at): void
    {
        $this->published_at = $published_at;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updated_at;
    }

    public function getDeletedAt(): ?DateTimeInterface
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?DateTimeInterface $deletedAt) : void
    {
        $this->deleted_at = $deletedAt;
    }

    public function getUser(): ?Admin
    {
        return $this->user;
    }

    public function setUser(?Admin $user): void
    {
        $this->user = $user;
        $this->setUserId($user?->getId());
    }

    public function getSiteId(): ?int
    {
        return $this->site_id;
    }

    public function setSiteId(?int $site_id): void
    {
        $this->site_id = $site_id;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): void
    {
        $this->site = $site;
        $this->setSiteId($site?->getId());
    }

    #[
        PreUpdate,
        PostLoad,
        PrePersist
    ]
    public function passwordCheck(
        PrePersistEventArgs|PostLoadEventArgs|PreUpdateEventArgs $event
    ) : void {
        $this->passwordBasedIdUpdatedAt($event);
    }
}
