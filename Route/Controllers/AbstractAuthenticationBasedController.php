<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Route\Controllers;

use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Admin;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\User;
use ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes\Dashboard as DashboardAttribute;
use ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes\User as UserAttribute;
use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Routing\AbstractController;
use ArrayAccess\TrayDigita\Util\Filter\DataNormalizer;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractAuthenticationBasedController extends AbstractController
{
    protected Users $users;

    protected ?User $user = null;

    protected ?Admin $admin = null;

    protected string $authPath = '/auth';

    protected string $userAuthPath;

    protected string $dashboardAuthPath;

    protected ?string $authenticationMethod = null;

    const TYPE_USER = 'user';

    const TYPE_ADMIN = 'admin';

    protected function getAuthenticationMethod() : ?string
    {
        return $this->authenticationMethod;
    }

    final public function beforeDispatch(ServerRequestInterface $request, string $method, ...$arguments)
    {
        $this->authPath = '/'.trim(DataNormalizer::normalizeUnixDirectorySeparator($this->authPath), '/');
        $this->authPath = $this->authPath ?: '/auth';
        $this->userAuthPath = UserAttribute::path($this->authPath);
        $this->dashboardAuthPath = DashboardAttribute::path($this->authPath);
        $this->users = $this->getModule(Users::class);
        $this->user = $this->users->getAdminAccount();
        $this->admin = $this->users->getUserAccount();
        $this->getView()->setParameter('user', $this->user);
        $this->getView()->setParameter('admin', $this->admin);
        return $this->doBeforeDispatch($request, $method, ...$arguments);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $method
     * @param ...$arguments
     */
    abstract public function doBeforeDispatch(
        ServerRequestInterface $request,
        string $method,
        ...$arguments
    );
}
