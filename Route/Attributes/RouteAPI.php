<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes;

use ArrayAccess\TrayDigita\Routing\Attributes\Group;
use ArrayAccess\TrayDigita\Routing\Router;
use ArrayAccess\TrayDigita\View\Interfaces\ViewInterface;
use Attribute;
use Psr\Http\Message\UriInterface;
use function in_array;
use function sprintf;
use function str_starts_with;
use function substr;

#[Attribute(Attribute::TARGET_CLASS)]
class RouteAPI extends Group
{
    const VERSION = '1';

    const VERSION_PREFIX = 'v';

    const API_PREFIX = 'api';

    const API_SUB_PREFIX = '';

    public function __construct(string $pattern = '')
    {
        $prefix = substr($pattern, 0, 1);
        // use static prefix
        $prefixRoute = static::prefix();
        // if contains delimiter
        if (in_array($prefix, Router::REGEX_DELIMITER)) {
            $prefixRoute = "$prefix^$prefixRoute";
            $pattern = substr($pattern, 1);
            $pattern = "(?:$pattern)";
        }
        $pattern = $prefixRoute . $pattern;
        parent::__construct($pattern);
    }

    public static function subPrefix(): string
    {
        return trim(static::API_SUB_PREFIX, '/');
    }

    public static function prefix(): string
    {
        $return = sprintf(
            '/%s/%s',
            trim(static::API_PREFIX, '/'),
            trim(static::VERSION_PREFIX . static::VERSION, '/')
        );
        $prefix = trim(static::subPrefix(), '/');
        if ($prefix !== '') {
            $return .= '/' . $prefix;
        }
        return $return;
    }

    public static function baseURI(
        ViewInterface $view,
        string $path = ''
    ): UriInterface {
        $currentPath = static::prefix() . '/';
        if (str_starts_with($path, '/')) {
            $path = substr($path, 1);
        }
        return $view->getBaseURI(
            $currentPath . $path
        );
    }
}
