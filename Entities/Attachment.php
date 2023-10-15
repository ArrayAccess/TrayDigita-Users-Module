<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Entities;

use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractAttachment;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * @property-read ?Admin $user
 */
#[Entity]
#[Table(
    name: self::TABLE_NAME,
    options: [
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'comment' => 'Attachments created by admin user',
    ]
)]
#[UniqueConstraint(
    name: 'unique_path_storage_type',
    columns: ['path', 'storage_type']
)]
#[Index(
    columns: ['storage_type', 'mime_type'],
    name: 'index_storage_type_mime_type'
)]
#[Index(
    columns: ['user_id'],
    name: 'relation_attachments_user_id_admins_id'
)]
#[Index(
    columns: ['name', 'file_name', 'status', 'mime_type', 'storage_type'],
    name: 'index_name_file_name_status_mime_type_storage_type'
)]
#[HasLifecycleCallbacks]
class Attachment extends AbstractAttachment
{
    const TABLE_NAME = 'attachments';

    #[
        JoinColumn(
            name: 'user_id',
            referencedColumnName: 'id',
            nullable: true,
            onDelete: 'SET NULL',
            options: [
                'relation_name' => 'relation_attachments_user_id_admins_id',
                'onUpdate' => 'CASCADE',
                'onDelete' => 'SET NULL'
            ],
        ),
        ManyToOne(
            targetEntity: Admin::class,
            cascade: [
                'persist'
            ],
            fetch: 'LAZY',
        )
    ]
    protected ?Admin $user = null;

    public function setUser(?Admin $user): void
    {
        $this->user = $user;
        $this->setUserId($user?->getId());
    }

    public function getUser(): ?Admin
    {
        return $this->user;
    }
}
