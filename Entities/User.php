<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractUser;
use ArrayAccess\TrayDigita\Database\TypeList;
use DateTimeImmutable;
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
 * @property-read DateTimeInterface $school_year
 * @property-read ?int $class_id
 * @property-read ?UserAttachment $attachment
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'User lists',
    ]
)]
#[UniqueConstraint(
    name: 'unique_username',
    columns: ['username']
)]
#[UniqueConstraint(
    name: 'unique_email',
    columns: ['email']
)]
#[UniqueConstraint(
    name: 'unique_identity_number',
    columns: ['identity_number']
)]
#[Index(
    columns: ['school_year', 'status'],
    name: 'index_school_year_status'
)]
#[Index(
    columns: ['username', 'status', 'role', 'first_name', 'last_name', 'school_year'],
    name: 'index_username_status_role_first_name_last_name_school_year'
)]
/*
#[Index(
    columns: ['class_id', 'role'],
    name: 'index_class_id_role'
)]
#[Index(
    columns: ['class_id'],
    name: 'relation_users_class_id_classes_id'
)]
*/

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
#[HasLifecycleCallbacks]
class User extends AbstractUser
{
    const TABLE_NAME = 'users';
    const ROLE_STUDENT  = 'student';
    const ROLE_ALUMNI   = 'alumni';
    const ROLE_GUARDIAN = 'guardian';
    const ROLE_GUEST = 'guest';

    protected array $availableRoles = [
        self::ROLE_STUDENT,
        self::ROLE_ALUMNI,
        self::ROLE_GUARDIAN,
        self::ROLE_GUEST,
    ];

    /*
    #[Column(
        name: 'class_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Class id'
        ]
    )]
    protected ?int $class_id = null;

    #[
        JoinColumn(
            name: 'class_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'CASCADE',
            options: [
                'relation_name' => 'relation_users_class_id_classes_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'CASCADE'
            ]
        ),
        ManyToOne(
            targetEntity: Classes::class,
            cascade: [
                "persist",
                "remove",
                "merge",
                "detach"
            ],
            fetch: 'EAGER'
        )
    ]
    protected ?Classes $class;
    */

    #[Column(
        name: 'related_user_id',
        type: Types::BIGINT,
        length: 20,
        nullable: true,
        options: [
            'unsigned' => true,
            'default' => null,
            'comment' => 'Relational user id'
        ]
    )]
    protected ?int $related_user_id = null;

    #[Column(
        name: 'school_year',
        type: TypeList::YEAR,
        nullable: false,
        options: [
            'comment' => 'User school year'
        ]
    )]
    protected DateTimeInterface $school_year;

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

    public function getSchoolYear(): DateTimeInterface
    {
        return $this->school_year;
    }

    public function setSchoolYear(DateTimeInterface|int|null $school_year): void
    {
        /** @noinspection DuplicatedCode */
        if (is_int($school_year)) {
            $school_year = (string) $school_year;
            if ($school_year < 1000) {
                do {
                    $school_year = "0$school_year";
                } while (strlen($school_year) < 4);
            }
            $school_year = substr($school_year, 0, 4);
            $school_year = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                "$school_year-01-01"
            )?:new DateTimeImmutable("$school_year-01-01 00:00:00");
        }

        $this->school_year = $school_year;
    }

    public function getClassId(): ?int
    {
        return $this->class_id;
    }

    /*
    public function setClassId(?int $class_id): void
    {
        $this->class_id = $class_id;
    }

    public function getClass(): ?Classes
    {
        return $this->class;
    }

    public function setClass(?Classes $class): void
    {
        $this->class = $class;
        $this->setClassId($class?->getId());
    }
    */

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
