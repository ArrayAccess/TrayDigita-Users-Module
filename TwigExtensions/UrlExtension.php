<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\TwigExtensions;

use ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes\DashboardAPI;
use ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes\RouteAPI;
use ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes\UserAPI;
use ArrayAccess\TrayDigita\View\Engines\TwigEngine;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UrlExtension extends AbstractExtension
{
    public function __construct(public readonly TwigEngine $engine)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'api_url',
                fn ($path = '') => RouteAPI::baseURI($this->engine->getView(), (string) $path)
            ),
            new TwigFunction(
                'user_api_url',
                fn ($path = '') => UserAPI::baseURI($this->engine->getView(), (string) $path)
            ),
            new TwigFunction(
                'dashboard_api_url',
                fn ($path = '') => DashboardAPI::baseURI($this->engine->getView(), (string) $path)
            )
        ];
    }
}
