<?php

namespace Abd\Docmaker\Traits;

use Illuminate\Support\Str;

trait ControllerTrait
{
    public function sliceControllerName($controller)
    {
        if (str_contains($controller, $this->namespace)) {
            return explode('_', Str::snake(substr($controller, strlen($this->namespace))))[0];
        }
    }

    public function controller()
    {
        $controller = app_path('Http/Controllers/' . $this->option('controller') . 'Controller.php');
        $controllers = glob(app_path('Http/Controllers') . '/*');
        if ($this->option('controller') && in_array($controller, $controllers)) {
            return $this->namespace . $this->option('controller') . 'Controller';
        } else {
            $this->error("$controller nomli controller topilmadi");
            die;
            return false;
        };
    }
}