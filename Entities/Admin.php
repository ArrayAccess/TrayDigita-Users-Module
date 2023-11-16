<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\App\Modules\Media\Entities\Attachment;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractUser;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @property-read ?int $site_id
 * @property-read ?Site $site
 * @property-read ?Attachment $attachment
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Administrator users',
        'priority' => 5,
    ]
)]
#[UniqueConstraint(
    name: 'unique_username_site_id',
    columns: ['username', 'site_id']
)]
#[UniqueConstraint(
    name: 'unique_email_site_id',
    columns: ['email', 'site_id']
)]
#[UniqueConstraint(
    name: 'unique_identity_number_site_id',
    columns: ['identity_number', 'site_id']
)]
#[Index(
    columns: ['username', 'status', 'role', 'first_name', 'last_name', 'site_id'],
    name: 'index_username_status_role_first_name_last_name_site_id'
)]
#[Index(
    columns: ['attachment_id'],
    name: 'relation_admins_attachment_id_attachments_id'
)]
#[Index(
    columns: ['role'],
    name: 'relation_admins_role_roles_identity'
)]
#[Index(
    columns: ['site_id'],
    name: 'relation_admins_site_id_sites_id'
)]
#[HasLifecycleCallbacks]
class Admin extends AbstractUser
{
    public const TABLE_NAME = 'admins';

    #[Column(
        name: 'identity_number',
        type: Types::STRING,
        length: 255,
        nullable: true,
        updatable: true,
        options: [
            'default' => null,
            'comment' => 'Unique identity number'
        ]
    )]
    protected ?string $identity_number = null;
    #[Column(
        name: 'username',
        type: Types::STRING,
        length: 255,
        nullable: false,
        updatable: true,
        options: [
            'comment' => 'Unique username'
        ]
    )]
    protected string $username;

    #[Column(
        name: 'email',
        type: Types::STRING,
        length: 320,
        nullable: false,
        updatable: true,
        options: [
            'comment' => 'Unique email'
        ]
    )]
    protected string $email;

    #[Column(
        name: 'site_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'default' => null,
            'unsigned' => true,
            'comment' => 'Site id'
        ]
    )]
    protected ?int $site_id = null;

    #[
        JoinColumn(
            name: 'site_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_admins_site_id_sites_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
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
            name: 'attachment_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_admins_attachment_id_attachments_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: Attachment::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?Attachment $attachment = null;

    #[
        JoinColumn(
            name: 'role',
            referencedColumnName: 'identity',
            nullable: true,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_admins_role_roles_identity',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
            ],
        ),
        ManyToOne(
            targetEntity: Role::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?Role $roleObject = null;

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

    public function getObjectRole(): Role
    {
        if (!$this->roleObject) {
            $this->roleObject = new Role();
            $this->roleObject->setIdentity($this->getRole());
            $this->roleObject->setName($this->getRole());
            $entity = $this->getEntityManager();
            $entity && $this->roleObject->setEntityManager($entity);
        }
        return $this->roleObject;
    }

    public function setRoleObject(Role $roleObject): void
    {
        $this->roleObject = $roleObject;
        $this->setRole($roleObject->getIdentity());
    }

    public function getAttachment(): ?Attachment
    {
        return $this->attachment;
    }

    public function setAttachment(?Attachment $attachment): void
    {
        $this->attachment = $attachment;
        $this->setAttachmentId($attachment?->getId());
    }
}
