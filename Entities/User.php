<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\App\Modules\Media\Entities\UserAttachment;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractUser;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @property-read ?int $site_id
 * @property-read ?Site $site
 * @property-read ?UserAttachment $attachment
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'User lists',
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
    columns: ['related_user_id'],
    name: 'relation_users_related_user_id_users_id'
)]
#[Index(
    columns: ['attachment_id'],
    name: 'relation_users_attachment_id_user_attachments_id'
)]
#[Index(
    columns: ['role'],
    name: 'relation_users_role_roles_identity'
)]
#[Index(
    columns: ['site_id'],
    name: 'relation_users_site_id_sites_id'
)]
#[HasLifecycleCallbacks]
class User extends AbstractUser
{
    public const TABLE_NAME = 'users';

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
                'relation_name' => 'relation_users_site_id_sites_id',
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

    #[Column(
        name: 'related_user_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'default' => null,
            'unsigned' => true,
            'comment' => 'Relational user id'
        ]
    )]
    protected ?int $related_user_id = null;

    #[
        JoinColumn(
            name: 'related_user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_users_related_user_id_users_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: User::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?User $related_user = null;

    #[
        JoinColumn(
            name: 'attachment_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_users_attachment_id_user_attachments_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: UserAttachment::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY'
        )
    ]
    protected ?UserAttachment $attachment = null;

    #[
        JoinColumn(
            name: 'role',
            referencedColumnName: 'identity',
            nullable: true,
            onDelete: 'RESTRICT',
            options: [
                'relation_name' => 'relation_users_role_roles_identity',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'RESTRICT'
            ],
        ),
        ManyToOne(
            targetEntity: Role::class,
            cascade: [
                'persist'
            ],
            fetch: 'EAGER'
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

    public function getRelatedUserId(): ?int
    {
        return $this->related_user_id;
    }

    public function setRelatedUserId(?int $related_user_id): void
    {
        $this->related_user_id = $related_user_id;
    }

    public function getRelatedUser(): ?User
    {
        return $this->related_user;
    }

    public function setRelatedUser(?User $related_user): void
    {
        $this->related_user = $related_user;
        $this->setRelatedUserId($related_user?->getId());
    }

    public function getAttachment(): ?UserAttachment
    {
        return $this->attachment;
    }

    public function setAttachment(?UserAttachment $attachment): void
    {
        $this->attachment = $attachment;
        $this->setAttachmentId($attachment?->getId());
    }

    #[
        PreUpdate,
        PostLoad,
        PrePersist
    ]
    public function relationIdCheck(
        PrePersistEventArgs|PostLoadEventArgs|PreUpdateEventArgs $event
    ) : void {
        if ($event instanceof PreUpdateEventArgs
            && $event->hasChangedField('related_user_id')
            && $event->getNewValue('related_user_id') === $this->getId()
        ) {
            $oldValue = $event->getOldValue('related_user_id');
            if ($oldValue !== null) {
                /**
                 * @var self $parent
                 */
                $parent = $event
                    ->getObjectManager()
                    ->getRepository($this::class)
                    ->find($this->getId())
                    ?->getRelatedUser();
                if ($parent?->getId() === $parent?->getRelatedUserId()) {
                    $parent = null;
                    $oldValue = null;
                }
            }
            $this->setRelatedUser($parent??null);
            $this->setRelatedUserId($oldValue);
            $event->setNewValue('related_user_id', $oldValue);
        } elseif (!$event instanceof PreUpdateEventArgs
            && $this->getRelatedUserId() === $this->getId()
        ) {
            $parent = $event
                ->getObjectManager()
                ->getRepository($this::class)
                ->find($this->getId())
                ?->getRelatedUser();
            if ($parent?->getId() === $parent?->getRelatedUserId()) {
                $parent = null;
            }
            // prevent
            $this->setRelatedUserId($parent?->getId());
            $this->setRelatedUser($parent);
            $q = $event
                ->getObjectManager()
                ->createQueryBuilder()
                ->update($this::class, 'x')
                ->set('x.related_user_id', ':val')
                ->where('x.id = :id')
                ->setParameters([
                    'val' => null,
                    'id' => $this->getId(),
                ]);
            $date = $this->getUpdatedAt();
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if ($date instanceof DateTimeInterface) {
                $date = str_starts_with($date->format('Y'), '-')
                    ? '0000-00-00 00:00:00'
                    : $date->format('Y-m-d H:i:s');
            }
            $q
                ->set('x.updated_at', ':updated_at')
                ->setParameter('updated_at', $date);
            $q->getQuery()->execute();
        }
    }
}
