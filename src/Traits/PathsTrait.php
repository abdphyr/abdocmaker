<?php

namespace Abd\Docmaker\Traits;

trait PathsTrait
{
    public function controllerFilePath($controller, $withCreate = true)
    {
        $filename = $this->sliceControllerName($controller);
        if ($withCreate && !file_exists($this->controllersPath)) {
            mkdir($this->controllersPath, 0777, true);
        }
        return $this->makePath($this->controllersPath, $filename) . '.php';
    }
    
    public function docFilePath($route, $withCreate = true)
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
        if ($withCreate && !file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = $this->makePath($dir, $action) . '.json';
        return compact('path', 'action');
    }

    public function makePath(...$folders)
    {
        return implode('/', $folders);
    }

    public function mainDocFilePath($withCreate = true)
    {
        $path = $this->docsPath . '/' . $this->mainDocFile . '.json';
        if ($withCreate && !file_exists($path)) {
            file_put_contents($path, file_get_contents(dirname(__DIR__) . '/assets/template.json'));
        }
        return $path;
    }
}
