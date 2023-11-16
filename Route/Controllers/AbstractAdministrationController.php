<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Route\Controllers;

use ArrayAccess\TrayDigita\Traits\Service\TranslatorTrait;
use ArrayAccess\TrayDigita\Util\Filter\DataNormalizer;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractAdministrationController extends AbstractAuthenticationBasedController
{
    use TranslatorTrait;

    protected bool $doRedirect = true;

    /**
     * Doing check
     *
     * @param ServerRequestInterface $request
     * @param string $method
     * @param ...$arguments
     * @noinspection PhpMissingReturnTypeInspection
     * @noinspection PhpDocSignatureIsNotCompleteInspection
     */
    final public function doBeforeDispatch(
        ServerRequestInterface $request,
        string $method,
        ...$arguments
    ) {
        $redirect = $this->doRedirect ? match ($this->getAuthenticationMethod()) {
            self::TYPE_ADMIN => $this->admin ? null : $this->dashboardAuthPath,
            self::TYPE_USER => $this->user ? null : $this->userAuthPath,
            default => null,
        } : null;
        return $redirect
            ? $this->redirect(
                $this
                    ->getView()
                    ->getBaseURI($redirect)
                    ->withQuery(
                        'redirect='
                        . DataNormalizer::normalizeUnixDirectorySeparator($request->getUri()->getPath())
                    )
            ) : $this->doAfterBeforeDispatch($request, $method, $arguments);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $method
     * @param ...$arguments
     */
    abstract public function doAfterBeforeDispatch(
        ServerRequestInterface $request,
        string $method,
        ...$arguments
    );
}
