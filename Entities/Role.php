<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Auth\Roles\Interfaces\RoleInterface;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
use ArrayAccess\TrayDigita\Exceptions\InvalidArgument\EmptyArgumentException;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4', // remove this or change to utf8 if not use mysql
        'collation' => 'utf8mb4_unicode_ci',  // remove this if not use mysql
        'comment' => 'Table roles'
    ]
)]
#[Index(
    columns: ['name'],
    name: 'index_name'
)]
#[HasLifecycleCallbacks]
/**
 * @property-read string $identity
 * @property-read string $name
 * @property-read ?string $description
 */
class Role extends AbstractEntity implements RoleInterface
{
    const TABLE_NAME = 'roles';

    private ?string $originIdentity = null;

    #[Id]
    #[Column(
        name: 'identity',
        type: Types::STRING,
        length: 128,
        updatable: true,
        options: [
            'comment' => 'Primary key role identity'
        ]
    )]
    protected string $identity;

    #[Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        options: [
            'comment' => 'Role name'
        ]
    )]
    protected string $name;

    #[Column(
        name: 'description',
        type: Types::TEXT,
        length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT,
        nullable: true,
        options: [
            'comment' => 'Role description'
        ]
    )]
    protected ?string $description = null;

    #[OneToMany(
        mappedBy: 'role',
        targetEntity: RoleCapability::class,
        cascade: [
            'detach',
            'merge',
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
        $identity = strtolower(trim($identity));
        if ($identity === '') {
            throw new EmptyArgumentException(
                'Identity could not being empty or contain whitespace only'
            );
        }
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

    public function getRole(): string
    {
        return $this->getIdentity();
    }

    public function getRoleCapability(): ?Collection
    {
        return $this->roleCapability;
    }

    #[
        PreUpdate,
        PrePersist
    ]
    public function postCheckChangeIdentity() : void
    {
        $this->identity = strtolower(trim($this->identity));
        if ($this->identity === '') {
            throw new EmptyArgumentException(
                'Identity could not being empty or contain whitespace only'
            );
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    #[PostLoad]
    public function postLoadIdentityLower(PostLoadEventArgs $eventArgs): void
    {
        $this->originIdentity ??= $this->identity;
        $this->identity = strtolower(trim($this->originIdentity));
    }

    public function serialize(): ?string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->unserialize(unserialize($data));
    }

    public function __serialize(): array
    {
        return [
            'identity' => $this->identity,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->identity = $data['identity'];
        $this->name = $data['name'];
        $this->description = $data['description'];
    }

    public function __toString(): string
    {
        return $this->getIdentity();
    }
}
