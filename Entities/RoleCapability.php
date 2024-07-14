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
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4', // remove this or change to utf8 if not use mysql
        'collation' => 'utf8mb4_unicode_ci',  // remove this if not use mysql
        'comment' => 'Role relation capability & meta',
        'priority' => 2,
        'primaryKey' => [
            'role_identity',
            'capability_identity'
        ]
    ]
)]
#[Index(
    name: 'relation_role_capabilities_identity_roles_identity_sites_id',
    columns: ['role_identity', 'site_id']
)]
#[Index(
    name: 'relation_roles_cap_cap_id_capabilities_identity_site_id',
    columns: ['capability_identity', 'site_id']
)]
#[HasLifecycleCallbacks]
class RoleCapability extends AbstractEntity
{
    public const TABLE_NAME = 'role_capabilities';
    
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

    #[JoinTable(name: Role::TABLE_NAME)]
    #[
        JoinColumn(
            name: 'role_identity',
            referencedColumnName: 'identity',
            nullable: false,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_role_capabilities_identity_roles_identity_sites_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
            ]
        ),
        JoinColumn(
            name: 'site_id',
            referencedColumnName: 'site_id',
            nullable: false,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_role_capabilities_identity_roles_identity_sites_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
            ]
        ),
        ManyToOne(
            targetEntity: Role::class,
            cascade: [
                "persist",
                "remove",
                // "merge",
                "detach"
            ],
            fetch: 'LAZY'
        )
    ]
    protected Role $role;

    #[JoinTable(name: Capability::TABLE_NAME)]
    #[
        JoinColumn(
            name: 'capability_identity',
            referencedColumnName: 'identity',
            nullable: false,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_roles_cap_cap_id_capabilities_identity_site_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
            ]
        ),
        JoinColumn(
            name: 'site_id',
            referencedColumnName: 'site_id',
            nullable: false,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_roles_cap_cap_id_capabilities_identity_site_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
            ]
        ),
        ManyToOne(
            targetEntity: Capability::class,
            cascade: [
                "persist",
                "remove",
                // "merge",
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
