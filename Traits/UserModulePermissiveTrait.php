<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Traits;

use ArrayAccess\TrayDigita\App\Modules\Users\Factory\CapabilityFactory;
use ArrayAccess\TrayDigita\Auth\Roles\Interfaces\PermissionInterface;
use ArrayAccess\TrayDigita\Container\Interfaces\SystemContainerInterface;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\CapabilityEntityFactoryInterface;
use ArrayAccess\TrayDigita\Database\Wrapper\PermissionWrapper;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;

trait UserModulePermissiveTrait
{
    use UserModuleAssertionTrait;

    protected PermissionInterface $permission;

    private bool $permissionResolved = false;

    private function resolvePermission(): self
    {
        if ($this->permissionResolved) {
            return $this;
        }

        $this->assertObjectUser();
        $this->permissionResolved = true;
        $container = $this->getContainer();
        $connection = $this->getConnection();
        $manager = $this->getManager();
        if (!$container->has(CapabilityEntityFactoryInterface::class)) {
            $container->set(
                CapabilityEntityFactoryInterface::class,
                static fn () => new CapabilityFactory()
            );
        }
        $hasPermission = $container->has(PermissionInterface::class);
        if ($hasPermission) {
            $permission = ContainerHelper::getNull(
                PermissionInterface::class,
                $container
            );
            if (!$permission instanceof PermissionInterface) {
                $container->remove(PermissionInterface::class);
                $hasPermission = false;
            }
        }
        if (!$hasPermission) {
            $permission = new PermissionWrapper(
                $connection,
                $container,
                $manager
            );
            if ($container instanceof SystemContainerInterface) {
                $container->set(PermissionInterface::class, $permission);
            } else {
                $container->set(PermissionInterface::class, fn () => $permission);
            }
        }

        $permission ??= ContainerHelper::service(
            PermissionInterface::class,
            $container
        );
        if (!$permission instanceof PermissionWrapper) {
            $container->remove(PermissionInterface::class);
            $permission = new PermissionWrapper(
                $connection,
                $container,
                $manager,
                $permission
            );
            $container->set(PermissionInterface::class, $permission);
        }
        $this->permission = $permission;
        if ($this->permission instanceof PermissionWrapper
            && !$this->permission->getCapabilityEntityFactory()
        ) {
            $this->permission->setCapabilityEntityFactory(new CapabilityFactory());
        }
        return $this;
    }

    /**
     * @return PermissionInterface
     */
    public function getPermission(): PermissionInterface
    {
        $container = $this->resolvePermission()->getContainer();
        $permission = ContainerHelper::service(PermissionInterface::class, $container);
        return $permission instanceof PermissionWrapper
            ? $permission
            : $this->permission;
    }
}
