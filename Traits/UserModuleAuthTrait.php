<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Traits;

use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Admin;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\User;
use ArrayAccess\TrayDigita\App\Modules\Users\Factory\AdminEntityFactory;
use ArrayAccess\TrayDigita\App\Modules\Users\Factory\UserEntityFactory;
use ArrayAccess\TrayDigita\Auth\Cookie\UserAuth;
use ArrayAccess\TrayDigita\Collection\Config;
use ArrayAccess\TrayDigita\Container\Interfaces\SystemContainerInterface;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractUser;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\UserEntityInterface;
use ArrayAccess\TrayDigita\Exceptions\Runtime\RuntimeException;
use ArrayAccess\TrayDigita\Http\Factory\ServerRequestFactory;
use ArrayAccess\TrayDigita\Http\ServerRequest;
use ArrayAccess\TrayDigita\Http\SetCookie;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\Util\Filter\DataNormalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use function filter_var;
use function is_numeric;
use function is_string;
use function max;
use function preg_replace;
use function time;
use function trim;
use const FILTER_VALIDATE_DOMAIN;

trait UserModuleAuthTrait
{
    use UserModuleAssertionTrait;

    const ADMIN_MODE = 'admin';

    const USER_MODE = 'user';

    private array $cookieNames = [
        self::USER_MODE => [
            'name' => 'auth_user',
            'lifetime' => 0,
            'wildcard' => false
        ],
        self::ADMIN_MODE => [
            'name' => 'auth_admin',
            'lifetime' => 0,
            'wildcard' => false
        ]
    ];

    private ?User $userAccount = null;

    private ?Admin $adminAccount = null;

    private bool $authProcessed = false;

    private string $currentMode = self::ADMIN_MODE;

    private bool $cookieResolved = false;

    private function resolveCookieName(): self
    {
        if ($this->cookieResolved) {
            return $this;
        }
        $this->assertObjectUser();

        $this->cookieResolved = true;
        $config = ContainerHelper::use(Config::class, $this->getContainer());
        $cookie = $config->get('cookie');
        if (!$cookie instanceof Config) {
            $cookie = new Config();
            $config->set('cookie', $cookie);
        }
        foreach ($this->cookieNames as $key => $names) {
            $cookieData = $cookie->get($key);
            $cookieData = $cookieData instanceof Config
                ? $cookieData
                : new Config();
            // replace
            $cookie->set($key, $cookieData);
            $cookieName = $cookieData->get('name');
            $cookieName = is_string($cookieName) && trim($cookieName) !== ''
                ? trim($cookieName)
                : $names['name'];
            $cookieName = preg_replace(
                '~[^!#$%&\'*+-.^_`|\~a-z0-9]~i',
                '',
                $cookieName
            );

            $cookieName = $cookieName === '' ? $names['name'] : $cookieName;
            $cookieLifetime = $cookieData->get('lifetime');
            $cookieLifetime = is_numeric($cookieLifetime) ? $cookieLifetime : 0;
            $cookieLifetime = max((int) $cookieLifetime, 0);
            $cookieWildcard = $cookieData->get('wildcard') === true;
            $this->cookieNames[$key]['name'] = $cookieName;
            $this->cookieNames[$key]['wildcard'] = $cookieWildcard;
            $this->cookieNames[$key]['lifetime'] = $cookieLifetime;
        }

        return $this;
    }

    public function isAuthProcessed(): bool
    {
        return $this->authProcessed;
    }

    private function doProcessAuth(): self
    {
        if (!$this->request || $this->authProcessed) {
            return $this;
        }
        $this->assertObjectUser();
        $this->authProcessed = true;
        $container = $this->getContainer();
        $userAuth = ContainerHelper::service(UserAuth::class, $container);

        $request = $this->getManager()->dispatch('auth.request', $this->request);
        $request = $request instanceof ServerRequestInterface
            ? $request
            : $this->request;
        $userAuth->getHashIdentity()->setUserAgent(
            $request->getHeaderLine('User-Agent')
        );

        $cookieNames = $this->getCookieNames();
        $cookieParams = $request->getCookieParams();
        $adminCookie = $cookieParams[$cookieNames[self::ADMIN_MODE]['name']]??null;
        $adminCookie = !is_string($adminCookie) ? $adminCookie : null;
        $userCookie  = $cookieParams[$cookieNames[self::USER_MODE]['name']]??null;
        $userCookie = is_string($userCookie) ? $userCookie : null;

        $this->userAccount = $userCookie ? $userAuth->getUser(
            $userCookie,
            $this->getUserEntityFactory()
        ) : null;
        $this->adminAccount = $adminCookie ? $userAuth->getUser(
            $adminCookie,
            $this->getAdminEntityFactory()
        ) : null;
        return $this;
    }

    private function createEntityFactoryContainer(): self
    {
        $this->assertObjectUser();
        $container = $this->getContainer();
        $hasUserEntity = $container->has(UserEntityFactory::class);
        $hasAdminEntity = $container->has(AdminEntityFactory::class);
        if ($hasUserEntity && $hasAdminEntity) {
            return $this;
        }
        if ($container instanceof SystemContainerInterface) {
            if (!$hasUserEntity) {
                $container->set(UserEntityFactory::class, UserEntityFactory::class);
            }
            if (!$hasUserEntity) {
                $container->set(AdminEntityFactory::class, AdminEntityFactory::class);
            }
            return $this;
        }
        if (!$hasUserEntity) {
            $container->set(
                UserEntityFactory::class,
                fn() => ContainerHelper::resolveCallable(UserEntityFactory::class, $container)
            );
        }
        if (!$hasAdminEntity) {
            $container->set(
                AdminEntityFactory::class,
                fn() => ContainerHelper::resolveCallable(AdminEntityFactory::class, $container)
            );
        }
        return $this;
    }

    public function getAdminEntityFactory() : AdminEntityFactory
    {
        try {
            return $this
                ->createEntityFactoryContainer()
                ->getContainer()
                ->get(AdminEntityFactory::class);
        } catch (Throwable) {
            return new AdminEntityFactory(
                $this->getConnection()
            );
        }
    }

    public function getUserEntityFactory() : UserEntityFactory
    {
        try {
            return $this
                ->createEntityFactoryContainer()
                ->getContainer()
                ->get(UserEntityFactory::class);
        } catch (Throwable) {
            return new UserEntityFactory(
                $this->getConnection()
            );
        }
    }

    public function setAsAdminMode(): void
    {
        $this->currentMode = self::ADMIN_MODE;
    }

    public function setAsUserMode(): void
    {
        $this->currentMode = self::ADMIN_MODE;
    }

    public function getCurrentMode(): string
    {
        return $this->currentMode;
    }

    public function isLoggedIn() : bool
    {
        return match ($this->getCurrentMode()) {
            self::ADMIN_MODE => $this->isAdminLoggedIn(),
            self::USER_MODE => $this->isUserLoggedIn(),
            default => false
        };
    }

    public function getAccount() : User|Admin|null
    {
        return match ($this->getCurrentMode()) {
            self::ADMIN_MODE => $this->getAdminAccount(),
            self::USER_MODE => $this->getUserAccount(),
            default => null
        };
    }

    public function getUserAccount(): ?User
    {
        return $this->doProcessAuth()->userAccount;
    }

    public function getAdminAccount(): ?Admin
    {
        return $this->doProcessAuth()->adminAccount;
    }

    /**
     * @return array{
     *      user: array{name:string, lifetime: int, wildcard: bool},
     *      admin: array{name:string, lifetime: int, wildcard: bool}
     *     }
     */
    public function getCookieNames(): array
    {
        return $this->resolveCookieName()->cookieNames;
    }

    /**
     * @param string $type
     * @return ?array{name:string, lifetime: int, wildcard: bool}
     */
    public function getCookieNameData(string $type): ?array
    {
        return $this->getCookieNames()[$type]??null;
    }

    public function sendAuthCookie(
        AbstractUser $userEntity,
        ResponseInterface $response
    ) : ResponseInterface {
        $this->assertObjectUser();
        $container = $this->getContainer();
        $userAuth = ContainerHelper::service(UserAuth::class, $container);

        if (!$userAuth instanceof UserAuth) {
            throw new RuntimeException(
                'Can not determine use auth object'
            );
        }
        $cookieName = $userEntity instanceof Admin
            ? 'admin'
            : ($userEntity instanceof User ? 'user' : null);
        $settings = $cookieName ? $this->getCookieNameData($cookieName) : null;
        if ($settings === null) {
            throw new RuntimeException(
                'Can not determine cookie type'
            );
        }
        $request = $this->request??ServerRequest::fromGlobals(
            ContainerHelper::use(
                ServerRequestFactory::class,
                $container
            ),
            ContainerHelper::use(
                StreamFactoryInterface::class,
                $container
            )
        );
        $domain = $request->getUri()->getHost();
        $newDomain = $this->getManager()?->dispatch(
            'auth.cookieDomain',
            $domain
        );

        $domain = is_string($newDomain) && filter_var(
            $newDomain,
            FILTER_VALIDATE_DOMAIN
        ) ? $newDomain : $domain;

        if ($settings['wildcard']) {
            $domain = DataNormalizer::splitCrossDomain($domain);
        }
        $cookie = new SetCookie(
            name: $settings['name'],
            value: $userAuth->getHashIdentity()->generate($userEntity->getId()),
            expiresAt: $settings['lifetime'] === 0 ? 0 : $settings['lifetime'] + time(),
            path: '/',
            domain: $domain
        );
        $cookieObject = $this->getManager()?->dispatch(
            'auth.cookieObject',
            $cookie
        );
        $cookie = $cookieObject instanceof SetCookie
            ? $cookieObject
            : $cookie;
        return $cookie->appendToResponse($response);
    }

    public function isAdminLoggedIn(): bool
    {
        return $this->getAdminAccount() !== null;
    }

    public function isUserLoggedIn(): bool
    {
        return $this->getUserAccount() !== null;
    }

    /**
     * @param int $id
     * @return ?Admin
     */
    public function getAdminById(int $id): ?UserEntityInterface
    {
        return $this->getAdminEntityFactory()->findById($id);
    }

    /**
     * @param string $username
     * @return ?Admin
     */
    public function getAdminByUsername(string $username): ?UserEntityInterface
    {
        return $this->getAdminEntityFactory()->findByUsername($username);
    }

    /**
     * @param int $id
     * @return ?User
     */
    public function getUserById(int $id): ?UserEntityInterface
    {
        return $this->getUserEntityFactory()->findById($id);
    }

    /**
     * @param string $username
     * @return ?User
     */
    public function getUserByUsername(string $username) : ?UserEntityInterface
    {
        return $this->getUserEntityFactory()->findByUsername($username);
    }
}
