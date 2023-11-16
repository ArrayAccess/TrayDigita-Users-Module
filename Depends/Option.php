<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Depends;

use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Options;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Site;
use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Database\Helper\Expression;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use function array_filter;
use function array_merge;
use function array_values;
use function is_int;
use function is_numeric;
use function is_string;
use function str_contains;

class Option extends AbstractRepositoryUserDepends
{
    public function getEntityManager() : EntityManagerInterface
    {
        return $this->users->getEntityManager();
    }

    public function determineSite($site, &$argumentValid = null) : ?Site
    {
        if ($site instanceof Site) {
            $argumentValid = true;
            if ($site->isPostLoad()) {
                return $site;
            }
            $site = $site->getId();
            if (!is_int($site)) {
                return null;
            }
        }

        $argumentValid = false;
        if ($site === null) {
            $argumentValid = true;
            return $this->users->getSite()->current();
        }
        if (is_numeric($site)) {
            $site = is_string($site)
                && !str_contains($site, '.')
                ? (int) $site
                : $site;
            $argumentValid = is_int($site);
            return $argumentValid ? $this
                ->users
                ->getConnection()
                ->findOneBy(
                    Site::class,
                    ['id' => $site]
                ) : null;
        }
        if (is_string($site)) {
            $argumentValid = true;
            return $this
                ->users
                ->getConnection()
                ->findOneBy(
                    Site::class,
                    ['domain' => $site]
                );
        }

        return null;
    }

    /**
     * @return ObjectRepository<Options>&Selectable<Options>
     */
    public function getRepository() : ObjectRepository&Selectable
    {
        return $this
            ->users
            ->getConnection()
            ->getRepository(Options::class);
    }

    public function getBatch(
        string $name,
        ?Site &$site = null,
        string ...$optionNames
    ): array {
        $site = $this->determineSite($site);
        $siteId = $site?->getId();
        $optionNames = array_merge([$name], $optionNames);
        $optionNames = array_filter($optionNames, 'is_string');
        return $this
            ->getRepository()
            ->findBy(
                [
                    'name' => Expression::in('name', array_values($optionNames)),
                    'site_id' => $siteId
                ]
            );
    }

    private function normalizeSiteId(Site|int|null $site = null) : ?int
    {
        $site ??= $this->users->getSite();
        return !$site ? null : (is_int($site) ? $site : $site->getId());
    }

    public function getOrCreate(
        string $name,
        &$siteObject = null,
        Site|false|null $site = false
    ): ?Options {
        $option = $this->get($name, $site, $siteObject);
        if (!$option) {
            $option = new Options();
            $option->setName($name);
            $option->setSiteId($siteObject);
            $option->setEntityManager($this->getEntityManager());
        }
        return $option;
    }

    public function saveBatch(
        Options ...$option
    ): void {
        $em = null;
        foreach ($option as $opt) {
            $em ??= $opt->getEntityManager()??$this->getEntityManager();
            $em->persist($opt);
        }
        $em?->flush();
    }

    /**
     * @param string $name
     * @param null $siteId
     * @param ?Sites $site
     * @return Options|null
     */
    public function get(
        string $name,
        ?Site &$site = null
    ) : ?Options {
        $siteId = $this->determineSite($site)?->getId();
        return $this
            ->getRepository()
            ->findOneBy([
                'name' => $name,
                'site_id' => $siteId
            ]);
    }

    public function save(Options $options): void
    {
        $em = $options->getEntityManager()??$this->getEntityManager();
        $em->persist($options);
        $em->flush();
    }

    public function set(
        string $name,
        mixed $value,
        ?bool $autoload = null,
        Site|false|null $site = false
    ): Options {
        $entity = $this->getOrCreate($name, $site);
        $entity->setValue($value);
        if ($autoload !== null) {
            $entity->setAutoload($autoload);
        }
        $this->save($entity);

        return $entity;
    }
}
