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
        $basePathActions = ['index', 'store'];
        $singlePathActions = ['show', 'update', 'destroy'];
        $action = '';
        if(in_array($route['action'], $basePathActions)) {
            $action = 'get-list-store';
        } else if(in_array($route['action'], $singlePathActions)) {
            $action = 'get-one-update-delete';
        } else {
            $action = $route['action'];
        }
        $dir = $this->makePath($this->docsPath, $route['folder']);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        return [$this->makePath($dir, $action) . '.json', $action];
    }

    public function makePath(...$folders)
    {
        return implode('/', $folders);
    }
}
