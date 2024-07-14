<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Auth\Roles\Interfaces\RoleInterface;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\CapabilityEntityInterface;
use ArrayAccess\TrayDigita\Exceptions\InvalidArgument\EmptyArgumentException;
use ArrayAccess\TrayDigita\Util\Filter\IterableHelper;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\Table;
use function preg_replace;
use function strtolower;
use function trim;

/**
 * @property-read string $identity
 * @property-read string $name
 * @property-read ?string $description
 * @property-read ?string $type
 * @property-read ?Collection<RoleCapability> $roleCapability
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4', // remove this or change to utf8 if not use mysql
        'collation' => 'utf8mb4_unicode_ci',  // remove this if not use mysql
        'comment' => 'Capabilities',
        'priority' => 0,
        'primaryKey' => [
            'identity',
            'site_id'
        ],
    ]
)]
#[Index(
    name: 'index_identity_site_id',
    columns: ['identity', 'site_id']
)]
#[Index(
    name: 'relation_capabilities_site_id_sites_id',
    columns: ['site_id']
)]
#[Index(
    name: 'index_type',
    columns: ['type']
)]
#[HasLifecycleCallbacks]
class Capability extends AbstractEntity implements CapabilityEntityInterface
{
    public const TABLE_NAME = 'capabilities';

    public const TYPE_GLOBAL = 'global';

    public const TYPE_GLOBAL_ALTERNATE = null;

    #[Id]
    #[Column(
        name: 'identity',
        type: Types::STRING,
        length: 128,
        updatable: true,
        options: [
            'comment' => 'Primary key capability identity'
        ]
    )]
    protected string $identity;

    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        options: [
            'comment' => 'Capability name'
        ]
    )]
    protected string $name;

    #[Column(
        name: 'description',
        type: Types::TEXT,
        length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT,
        nullable: true,
        options: [
            'comment' => 'Capability description'
        ]
    )]
    protected ?string $description = null;

    #[Column(
        name: 'type',
        type: Types::STRING,
        length: 20,
        nullable: true,
        options: [
            'default' => self::TYPE_GLOBAL,
            'comment' => 'Capability type. user -> as users, admin -> as admins user & null/empty as global'
        ]
    )]
    protected ?string $type = null;

    #[Column(
        name: 'site_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Site id'
        ]
    )]
    protected ?int $site_id = null;

    #[
        JoinColumn(
            name: 'site_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_capabilities_site_id_sites_id',
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

    /**
     * @var ?Collection<RoleCapability> $roleCapability
     */
    #[OneToMany(
        targetEntity: RoleCapability::class,
        mappedBy: 'capability',
        cascade: [
            'detach',
            // "merge",
            'persist',
            'remove',
        ],
        fetch: 'LAZY'
    )]
    protected ?Collection $roleCapability = null;

    public function getIdentity(): string
    {
        return $this->identity;
    }

    public function setIdentity(string $identity): void
    {
        $this->identity = $identity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $this->normalizeType($type);
    }

    public function normalizeType(?string $type): string
    {
        return $type && $type !== self::TYPE_GLOBAL ? match (trim($type)) {
            '' => self::TYPE_GLOBAL_ALTERNATE,
            default => strtolower(trim($type))
        } : self::TYPE_GLOBAL_ALTERNATE;
    }

    public function getNormalizeType() : string
    {
        return $this->normalizeType($this->getType());
    }

    public function isType(?string $type): bool
    {
        return $this->normalizeType($type) === $this->getNormalizeType();
    }

    public function isGlobal() : bool
    {
        $type = $this->getType();
        $type = is_string($type) ? strtolower(trim($type)) : $type;
        return $type === '' || $type === null || $type === self::TYPE_GLOBAL;
    }

    /**
     * @return ?Collection<RoleCapability>
     */
    public function getRoleCapability(): ?Collection
    {
        return $this->roleCapability;
    }

    public function has(RoleInterface|string $role) : bool
    {
        $role = is_object($role) ? $role->getRole() : $role;
        return $this
            ->getRoleCapability()
            ->exists(static fn ($i, RoleCapability $r) => $r->getRoleIdentity() === $role);
    }

    /**
     * @return iterable<Role>
     */
    public function getRoles(): iterable
    {
        return IterableHelper::each(
            $this->getRoleCapability(),
            static function (&$key, RoleCapability $r) {
                $key = $r->getRoleIdentity();
                return $r->getRole();
            }
        );
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
        PostLoad,
        PrePersist
    ]
    public function postLoadPersistEventChange(
        PrePersistEventArgs|PostLoadEventArgs $event
    ) : void {
        $isPostLoad = $event instanceof PostLoadEventArgs;
        if ($isPostLoad) {
            $normalizeType = $this->getNormalizeType();
            if ($normalizeType !== $this->type) {
                $this->type = $normalizeType;
            }
        }
        $this->identity = strtolower(trim($this->identity));
        $this->identity = preg_replace('~[\s_]+~', '_', $this->identity);
        $this->identity = trim($this->identity, '_');
        if (! $isPostLoad && $this->identity === '') {
            throw new EmptyArgumentException(
                'Identity could not being empty or contain whitespace only'
            );
        }
    }
}
