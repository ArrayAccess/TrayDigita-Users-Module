<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Route\Controllers;

use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractDashboardController extends AbstractAdministrationController
{
    protected ?string $authenticationMethod = self::TYPE_ADMIN;

    /**
     * @param ServerRequestInterface $request
     * @param string $method
     * @param ...$arguments
     */
    public function doAfterBeforeDispatch(
        ServerRequestInterface $request,
        string $method,
        ...$arguments
    ) {
    }
}
