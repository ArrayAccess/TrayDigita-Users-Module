<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Route\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function is_string;
use function reset;
use function str_ends_with;
use function str_starts_with;

abstract class AbstractUserController extends AbstractAdministrationController
{
    protected ?string $authenticationMethod = self::TYPE_USER;

    /**
     * @param ServerRequestInterface $request
     * @param string $method
     * @param ...$arguments
     * @return ResponseInterface|void|null
     */
    public function doAfterBeforeDispatch(
        ServerRequestInterface $request,
        string $method,
        ...$arguments
    ) {
        $reset = reset($arguments);
        if ($request->getMethod() !== 'GET'
            || !($path = (reset($reset)?:[])[0]??null)
            || !is_string($path)
        ) {
            return null;
        }
        if (($end = str_ends_with($path, '//')) || str_starts_with($path, '//')) {
            return $this->redirect(
                $this->getView()->getBaseURI(
                    '/'.
                    trim($path, '/')
                    . ($end ? '/': '')
                )
            );
        }
    }
}
