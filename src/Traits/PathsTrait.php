<?php

namespace Abd\Docmaker\Traits;

trait PathsTrait
{
    public function controllerFilePath($controller)
    {
        $filename = $this->sliceControllerName($controller);
        if (!file_exists($this->controllersPath)) {
            mkdir($this->controllersPath, 0777, true);
        }
        return $this->makePath($this->controllersPath, $filename) . '.php';
    }
    
    public function docFilePath($route)
    {
        $dir = $this->makePath($this->docsPath, $route['folder']);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        return $this->makePath($dir, $route['action']) . '.json';
    }

    public function makePath(...$folders)
    {
        return implode('/', $folders);
    }
}
