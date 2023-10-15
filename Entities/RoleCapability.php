<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractEntity;
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
        'charset' => 'utf8mb4', // remove this or change to utf8 if not use mysql
        'collation' => 'utf8mb4_unicode_ci',  // remove this if not use mysql
        'comment' => 'Role relation capability & meta',
        'primaryKey' => [
            'class_id',
            'name'
        ]
    ]
)]
#[Index(
    columns: ['role_identity'],
    name: 'relation_capabilities_role_identity_roles_identity'
)]
#[Index(
    columns: ['capability_identity'],
    name: 'relation_capabilities_capability_identity_capabilities_identity'
)]
#[HasLifecycleCallbacks]
class RoleCapability extends AbstractEntity
{
    const TABLE_NAME = 'role_capabilities';
    
    #[Id]
    #[Column(
        name: 'role_identity',
        type: Types::STRING,
        length: 128,
        updatable: true,
        options: [
            'comment' => 'Primary key composite identifier'
        ]
    )]
    protected string $role_identity;

    #[Id]
    #[Column(
        name: 'capability_identity',
        type: Types::STRING,
        length: 128,
        updatable: true,
        options: [
            'comment' => 'Primary key composite identifier'
        ]
    )]
    protected string $capability_identity;

    #[
        JoinColumn(
            name: 'role_identity',
            referencedColumnName: 'identity',
            nullable: false,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_capabilities_role_identity_roles_identity',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
            ]
        ),
        ManyToOne(
            targetEntity: Role::class,
            cascade: [
                "persist",
                "remove",
                "merge",
                "detach"
            ],
            fetch: 'LAZY'
        )
    ]
    protected Role $role;

    #[
        JoinColumn(
            name: 'capability_identity',
            referencedColumnName: 'identity',
            nullable: false,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_capabilities_capability_identity_capabilities_identity',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
            ]
        ),
        ManyToOne(
            targetEntity: Capability::class,
            cascade: [
                "persist",
                "remove",
                "merge",
                "detach"
            ],
            fetch: 'LAZY'
        )
    ]
    protected Capability $capability;

    public function getRoleIdentity(): string
    {
        return $this->role_identity;
    }

    public function setRoleIdentity(string $role_identity): void
    {
        $this->role_identity = $role_identity;
    }

    public function getCapabilityIdentity(): string
    {
        return $this->capability_identity;
    }

    public function setCapabilityIdentity(string $capability_identity): void
    {
        $this->capability_identity = $capability_identity;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
        $this->setRoleIdentity($role->getIdentity());
    }

    public function getCapability(): Capability
    {
        return $this->capability;
    }

    public function setCapability(Capability $capability): void
    {
        $this->capability = $capability;
        $this->setCapabilityIdentity($capability->getIdentity());
    }
}
