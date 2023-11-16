<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Depends;

use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Admin;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Site;
use ArrayAccess\TrayDigita\Exceptions\Runtime\RuntimeException;
use DateTimeInterface;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\PrePersist;
use Psr\Http\Message\ServerRequestInterface;
use function preg_match;
use const PREG_UNMATCHED_AS_NULL;

#[HasLifecycleCallbacks]
class SingleSiteEntity extends Site
{
    public function __construct(
        public readonly ServerRequestInterface $request
    ) {
        [$main, $alias, $host] = self::parseHost($this->request);
        $this->domain = $main??$host;
        $this->id = 0;
        $this->name = $this->domain;
        $this->domain_alias = $alias??$this->domain_alias;
        parent::__construct();
    }

    public function getId(): ?int
    {
        return null;
    }

    /**
     * @param ServerRequestInterface $request
     * @param $match
     * @return array
     */
    public static function parseHost(
        ServerRequestInterface $request,
        &$match = null
    ): array {
        $host = $request->getUri()->getHost();
        preg_match(
            '~^(?<subdomain>[^.]+)\.(?<host>.+\.[^.]+)$~',
            $host,
            $match,
            PREG_UNMATCHED_AS_NULL
        );
        $mainDomain = $match ? $match['host'] : null;
        $subDomain = $match ? $match['subdomain'] : null;
        return [
            $mainDomain,
            $subDomain,
            $host
        ];
    }

    public function __set(string $name, $value): void
    {
        // none
    }

    public function setUuid(string $uuid): void
    {
    }

    public function setDomain(string $domain): void
    {
    }

    public function setDomainAlias(?string $domain_alias): void
    {
    }

    public function setUserId(?int $user_id): void
    {
    }

    public function setStatus(string $status): void
    {
    }

    public function setDeletedAt(?DateTimeInterface $deleted_at): void
    {
    }

    public function setUser(?Admin $user): void
    {
    }

    #[PrePersist]
    public function prePersist()
    {
        throw new RuntimeException(
            'Default entity can not be save'
        );
    }
}
