<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users;

use ArrayAccess\TrayDigita\App\Modules\Users\Traits\UserModuleAuthTrait;
use ArrayAccess\TrayDigita\App\Modules\Users\Traits\UserModuleDependsTrait;
use ArrayAccess\TrayDigita\App\Modules\Users\Traits\UserModuleEventTrait;
use ArrayAccess\TrayDigita\App\Modules\Users\Traits\UserModulePermissiveTrait;
use ArrayAccess\TrayDigita\App\Modules\Users\Traits\UserModuleSite;
use ArrayAccess\TrayDigita\App\Modules\Users\TwigExtensions\UrlExtension;
use ArrayAccess\TrayDigita\Container\Interfaces\SystemContainerInterface;
use ArrayAccess\TrayDigita\Http\ServerRequest;
use ArrayAccess\TrayDigita\Middleware\AbstractMiddleware;
use ArrayAccess\TrayDigita\Module\AbstractModule;
use ArrayAccess\TrayDigita\Traits\Database\ConnectionTrait;
use ArrayAccess\TrayDigita\Traits\Service\TranslatorTrait;
use ArrayAccess\TrayDigita\Traits\View\ViewTrait;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\View\Engines\TwigEngine;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * @method SystemContainerInterface getContainer()
 */
final class Users extends AbstractModule
{
    use TranslatorTrait,
        ViewTrait,
        UserModuleDependsTrait,
        ConnectionTrait,
        UserModuleEventTrait,
        UserModuleAuthTrait,
        UserModulePermissiveTrait;

    protected string $name = 'Users & Auth';

    /**
     * @var int -> very important
     */
    protected int $priority = PHP_INT_MIN + 1;

    private bool $didInit = false;

    private ?ServerRequestInterface $request = null;

    public function getName(): string
    {
        return $this->translateContext(
            'Users & Auth',
            'users-module',
            'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Module that support users & authentication',
            'users-module',
            'module'
        );
    }

    protected function doInit(): void
    {
        /** @noinspection DuplicatedCode */
        if ($this->didInit) {
            return;
        }

        $this->didInit = true;
        Consolidation::registerAutoloader(__NAMESPACE__, __DIR__);
        $kernel = $this->getKernel();
        $kernel->registerControllerDirectory(__DIR__ .'/Controllers');
        $this->getTranslator()?->registerDirectory('module', __DIR__ . '/Languages');
        $this->getConnection()->registerEntityDirectory(__DIR__.'/Entities');
        $twig = $this->getView()->getEngine('twig');
        ($twig instanceof TwigEngine ? $twig : null)
            ->addExtension(new UrlExtension($twig));
        unset($twig);
        // stop here if config error
        if ($kernel->getConfigError()) {
            return;
        }
        $manager = $this->getManager();
        $manager->attachOnce('view.beforeRender', [$this, 'eventViewBeforeRender']);
        $manager->attachOnce('view.bodyAttributes', [$this, 'eventViewBodyAttributes']);
        $kernel
            ->getHttpKernel()
            ->addMiddleware(new class($this->getContainer(), $this) extends AbstractMiddleware {
                protected int $priority = PHP_INT_MAX - 10;
                public function __construct(
                    ContainerInterface $container,
                    private readonly Users $auth
                ) {
                    parent::__construct($container);
                }

                protected function doProcess(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
                {
                    $this->auth->setRequest($request);
                    return $request;
                }
            });
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request ??= ServerRequest::fromGlobals(
            ContainerHelper::use(ServerRequestFactoryInterface::class, $this->getContainer()),
            ContainerHelper::use(StreamFactoryInterface::class, $this->getContainer())
        );
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }
}
