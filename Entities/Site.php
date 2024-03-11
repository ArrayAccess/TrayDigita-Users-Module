<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\AvailabilityStatusEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\IdentityBasedEntityInterface;
use ArrayAccess\TrayDigita\Database\Entities\Traits\AvailabilityStatusTrait;
use ArrayAccess\TrayDigita\Util\Generator\UUID;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;
use function debug_backtrace;
use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const PHP_INT_MIN;

/**
 * Entity to determine site & record
 *
 * @property-read int $id
 * @property-read string $uuid
 * @property-read string $name
 * @property-read string $domain
 * @property-read ?string $domain_alias
 * @property-read ?string $description
 * @property-read string $status
 * @property-read DateTimeInterface $created_at
 * @property-read DateTimeInterface $updated_at
 * @property-read ?DateTimeInterface $deleted_at
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Sites',
        'priority' => PHP_INT_MIN + 1000,
    ]
)]
#[UniqueConstraint(
    name: 'unique_uuid',
    columns: ['uuid'],
)]
#[UniqueConstraint(
    name: 'unique_domain',
    columns: ['domain'],
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_sites_user_id_admins_id'
)]
#[Index(
    columns: ['domain', 'domain_alias'],
    name: 'index_domain_domain_alias'
)]
#[Index(
    columns: ['name', 'domain', 'domain_alias', 'status'],
    name: 'index_name_domain_domain_alias_status'
)]
#[HasLifecycleCallbacks]
class Site extends AbstractEntity implements IdentityBasedEntityInterface, AvailabilityStatusEntityInterface
{
    public const TABLE_NAME = 'sites';

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
            'comment' => 'Attachment Id'
        ]
    )]
    protected int $id;

    #[Column(
        name: 'uuid',
        type: Types::STRING,
        length: 36,
        updatable: false,
        options: [
            'comment' => 'Site UUID'
        ]
    )]
    protected string $uuid;

    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: [
            'comment' => 'Site name'
        ]
    )]
    protected string $name;

    #[Column(
        name: 'domain',
        type: Types::STRING,
        length: 255,
        unique: true,
        nullable: false,
        options: [
            'comment' => 'Site domain'
        ]
    )]
    protected string $domain;

    #[Column(
        name: 'domain_alias',
        type: Types::STRING,
        length: 64,
        nullable: true,
        options: [
            'default' => null,
            'comment' => 'Site domain alias (formerly use subdomain)'
        ]
    )]
    protected ?string $domain_alias = null;

    #[Column(
        name: 'description',
        type: Types::TEXT,
        length: 4294967295,
        nullable: true,
        options:  [
            'default' => null,
            'comment' => 'Site description'
        ]
    )]
    protected ?string $description = null;

    #[Column(
        name: 'user_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        updatable: true,
        options: [
            'default' => null,
            'unsigned' => true,
            'comment' => 'Site Owner'
        ]
    )]
    protected ?int $user_id = null;

    #[Column(
        name: 'status',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: [
            'comment' => 'Announcement status'
        ]
    )]
    protected string $status;

    #[Column(
        name: 'created_at',
        type: Types::DATETIME_IMMUTABLE,
        updatable: false,
        options: [
            'default' => 'CURRENT_TIMESTAMP',
            'comment' => 'Announcement created time'
        ]
    )]
    protected DateTimeInterface $created_at;

    #[Column(
        name: 'updated_at',
        type: Types::DATETIME_IMMUTABLE,
        unique: false,
        updatable: false,
        options: [
            'attribute' => 'ON UPDATE CURRENT_TIMESTAMP',
            'default' => '0000-00-00 00:00:00',
            'comment' => 'Announcement update time'
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
            'comment' => 'Announcement delete time'
        ]
    )]
    protected ?DateTimeInterface $deleted_at = null;

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_sites_user_id_admins_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
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

    /**
     * Allow associations mapping
     * @see jsonSerialize()
     *
     * @var bool
     */
    protected bool $entityAllowAssociations = true;

    private bool $postLoad = false;

    public function __construct()
    {
        $this->uuid = UUID::v4();
        $this->domain_alias = 'www';
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable('0000-00-00 00:00:00');
        $this->deleted_at = null;
    }

    public function getId(): ?int
    {
        return $this->id??null;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getDomainAlias(): ?string
    {
        return $this->domain_alias;
    }

    public function setDomainAlias(?string $domain_alias): void
    {
        $this->domain_alias = $domain_alias;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
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

    public function setDeletedAt(?DateTimeInterface $deleted_at): void
    {
        $this->deleted_at = $deleted_at;
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

    final public function isPostLoad(): bool
    {
        return $this->postLoad;
    }

    #[PostLoad]
    final public function finalPostLoaded(PostLoadEventArgs $postLoadEventArgs): void
    {
        $this->postLoad = true;
    }
}
