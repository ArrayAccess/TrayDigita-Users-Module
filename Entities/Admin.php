<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Auth\Roles\SuperAdminRole;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractUser;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @property-read ?Attachment $attachment
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Administrator users'
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
    columns: ['username', 'status', 'role', 'first_name', 'last_name'],
    name: 'index_username_status_role_first_name_last_name'
)]
#[Index(
    columns: ['attachment_id'],
    name: 'relation_admins_attachment_id_attachments_id'
)]
#[Index(
    columns: ['role'],
    name: 'relation_admins_role_roles_identity'
)]
#[HasLifecycleCallbacks]
class Admin extends AbstractUser
{
    const TABLE_NAME = 'admins';

    const ROLE_SUPER_ADMIN = SuperAdminRole::NAME;
    // administrator and it was co admin
    const ROLE_ADMIN = 'admin';
    const ROLE_PRESIDENT = 'president';
    const ROLE_VICE_PRESIDENT = 'vice_president';
    const ROLE_RECTOR = 'rector';
    const ROLE_VICE_RECTOR = 'vice_rector';
    // dean
    const ROLE_DEAN = 'dean';
    const ROLE_VICE_DEAN = 'vice_dean';
    // faculty
    const ROLE_HEAD_FACULTY = 'head_faculty';
    const ROLE_VICE_HEAD_FACULTY = 'vice_head_faculty';

    // department
    const ROLE_HEAD_DEPARTMENT = 'head_department';
    const ROLE_VICE_HEAD_DEPARTMENT = 'vice_head_department';

    // headmaster
    const ROLE_HEADMASTER = 'headmaster';
    const ROLE_VICE_HEADMASTER = 'vice_headmaster';
    // hrd
    const ROLE_HUMAN_RESOURCE_DEPARTMENT = 'human_resource_department';
    const ROLE_HUMAN_RESOURCE_MANAGEMENT = 'human_resource_management';

    // lecturer
    const ROLE_LECTURER = 'lecturer';
    const ROLE_TEACHER = 'teacher';
    const ROLE_COUNSELING_GUIDANCE = 'counseling_guidance';
    const ROLE_SUPERVISOR = 'supervisor';
    const ROLE_LIBRARIAN = 'librarian';
    // staff
    const ROLE_OFFICE_STAFF = 'office_staff';
    // treasurer
    const ROLE_TREASURER = 'treasurer';
    // office admin
    const ROLE_OFFICE_ADMINISTRATION = 'office_admin';
    // other staff / worker
    const ROLE_STAFF = 'staff';

    protected array $availableRoles = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_PRESIDENT,
        self::ROLE_VICE_PRESIDENT,
        self::ROLE_RECTOR,
        self::ROLE_VICE_RECTOR,
        self::ROLE_DEAN,
        self::ROLE_VICE_DEAN,
        self::ROLE_HEAD_FACULTY,
        self::ROLE_VICE_HEAD_FACULTY,
        self::ROLE_HEAD_DEPARTMENT,
        self::ROLE_VICE_HEAD_DEPARTMENT,
        self::ROLE_HEADMASTER,
        self::ROLE_VICE_HEADMASTER,
        self::ROLE_HUMAN_RESOURCE_DEPARTMENT,
        self::ROLE_HUMAN_RESOURCE_MANAGEMENT,
        self::ROLE_LECTURER,
        self::ROLE_TEACHER,
        self::ROLE_COUNSELING_GUIDANCE,
        self::ROLE_SUPERVISOR,
        self::ROLE_LIBRARIAN,
        self::ROLE_OFFICE_STAFF,
        self::ROLE_TREASURER,
        self::ROLE_OFFICE_ADMINISTRATION,
        self::ROLE_STAFF,
    ];

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
