<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes;

use ArrayAccess\TrayDigita\Routing\Attributes\Group;
use ArrayAccess\TrayDigita\Routing\Router;
use ArrayAccess\TrayDigita\Util\Filter\DataNormalizer;
use Attribute;
use function in_array;
use function str_starts_with;
use function substr;

#[Attribute(Attribute::TARGET_CLASS)]
class Dashboard extends Group
{
    protected static string $prefix = 'dashboard';

    public function __construct(string $pattern = '')
    {
        $prefix = substr($pattern, 0, 1);
        // use static prefix
        $prefixRoute = static::prefix();
        if (!str_starts_with($prefixRoute, '/')) {
            $prefixRoute = "/$prefixRoute";
        }

        // if contains delimiter
        if (in_array($prefix, Router::REGEX_DELIMITER)) {
            $prefixRoute = "$prefix^$prefixRoute";
            $pattern = substr($pattern, 1);
            $pattern = "(?:$pattern)";
        }
        $pattern = $prefixRoute . $pattern;
        parent::__construct($pattern);
    }

    public static function prefix(): string
    {
        $prefix = static::$prefix;
        return str_starts_with($prefix, '/')
            ? $prefix
            : "/$prefix";
    }

    public static function path(string $path = ''): string
    {
        $prefix = static::prefix();
        if ($path && !str_starts_with($path, '/')) {
            $path = "/$path";
        }

        return $prefix . $path;
    }

    public static function setPrefix(string $prefix): void
    {
        static::$prefix = trim(
            DataNormalizer::normalizeUnixDirectorySeparator($prefix),
            '/'
        );
    }
}
