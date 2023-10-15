<?php
/** @noinspection PhpUnused */
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
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\Table;

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
        'comment' => 'Capabilities'
    ]
)]
#[Index(
    columns: ['name'],
    name: 'index_name'
)]
#[Index(
    columns: ['type'],
    name: 'index_type'
)]
#[HasLifecycleCallbacks]
class Capability extends AbstractEntity implements CapabilityEntityInterface
{
    const TABLE_NAME = 'capabilities';
    const TYPE_USER = 'user';
    const TYPE_ADMIN = 'admin';

    const TYPE_GLOBAL = null;
    const TYPE_GLOBAL_ALTERNATE = 'global';

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

    #[OneToMany(
        mappedBy: 'capability',
        targetEntity: RoleCapability::class,
        cascade: [
            'detach',
            'merge',
            'persist',
            'remove',
        ],
        fetch: 'LAZY'
    )]
    /**
     * @var ?Collection<RoleCapability> $roleCapability
     */
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
        $type = is_string($type) ? strtolower(trim($type)) : $type;
        $this->type = $type;
    }

    public function isGlobal() : bool
    {
        $type = $this->getType();
        $type = is_string($type) ? strtolower(trim($type)) : $type;
        return $type === '' || $type === null || $type === self::TYPE_GLOBAL;
    }

    public function isUser() : bool
    {
        $type = $this->getType();
        return (is_string($type) ? trim($type) : $type) === self::TYPE_USER;
    }

    public function isAdmin() : bool
    {
        $type = $this->getType();
        return (is_string($type) ? trim($type) : $type) === self::TYPE_ADMIN;
    }

    /**
     * @return ?Collection<RoleCapability>
     */
    public function getRoleCapability(): ?Collection
    {
        return $this->roleCapability;
    }

    /** @noinspection PhpUnusedParameterInspection */
    #[
        PostLoad,
        PrePersist
    ]
    public function postLoadChangeIdentity(
        PrePersistEventArgs|PostLoadEventArgs $event
    ) : void {
        $this->identity = strtolower(trim($this->identity));
        $this->identity = preg_replace('~[\s_]+~', '_', $this->identity);
        $this->identity = trim($this->identity, '_');
        if ($this->identity === '') {
            throw new EmptyArgumentException(
                'Identity could not being empty or contain whitespace only'
            );
        }
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
}
