<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Users\Traits;

use ArrayAccess\TrayDigita\App\Modules\Users\Depends\Option;
use ArrayAccess\TrayDigita\App\Modules\Users\Depends\Sites;

trait UserModuleDependsTrait
{
    use UserModuleAssertionTrait;

    private ?Option $option = null;
    private ?Sites $site = null;

    public function getOption(): Option
    {
        if ($this->option) {
            return $this->option;
        }
        $this->assertObjectUser();
        $this->option = new Option($this);
        return $this->option;
    }

    public function getSite(): Sites
    {
        if ($this->site) {
            return $this->site;
        }
        $this->assertObjectUser();
        $this->site = new Sites($this);
        return $this->site;
    }
}
