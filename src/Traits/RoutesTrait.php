<?php

namespace Abd\Docmaker\Traits;

use Illuminate\Support\Facades\Route;

trait RoutesTrait
{

    public function getRoute($search)
    {
        foreach ($this->allroutes as $routes) {
            foreach ($routes as $route => $properties) {
                if (str_contains($route, $search)) {
                    return $properties;
                };
            }
        }
    }

    public function prepareRoute($route)
    {
        $route = callerOfArray($route);
        if (is_string($route)) {
            $route = $this->getRoute($route);
        }
        if (!empty(getterArray($route, 'parameters', 'params'))) {
            $route['uri'] = route($route['name'], getterArray($route, 'parameters', 'params'));
        }
        if ($route['auth']) {
            $access_token = $this->makeRequestService->resolveToken($this->getRoute('login'));
            $route['headers']["Authorization"] = "Bearer $access_token";
        }
        return $route;
    }

    public function getRoutes()
    {
        $allCachedRoutes = $this->getRoutesFromLaravelCache();
        $this->allroutes = $this->getControllerRoutes($allCachedRoutes);
        if ($controller = $this->controller()) {
            if (isset($allCachedRoutes[$controller])) {
                $writable[$controller] = $allCachedRoutes[$controller];
                $writable[$this->namespace . "AuthController"] = $allCachedRoutes[$this->namespace . "AuthController"];
                $this->writeControllerRoutes($writable);
            }
            if (isset($this->allroutes[$controller])) {
                if ($this->controllerActions()) $this->actions = array_merge($this->actions, $this->controllerActions());
                if ($this->index()) $this->actions[] = 'index';
                if ($this->show()) $this->actions[] = 'show';
                if ($this->store()) $this->actions[] = 'store';
                if ($this->update()) $this->actions[] = 'update';
                if ($this->destroy()) $this->actions[] = 'destroy';
                $routes = $this->allroutes[$controller];
                if (!empty($this->actions)) {
                    $routes = array_filter($routes, function ($route) {
                        return in_array($route['action'], $this->actions);
                    });
                }
                return $routes;
            } else {
                $this->error("$controller nomli controller topilmadi");
                die;
            }
        } else {
            $this->writeControllerRoutes($allCachedRoutes);
            $routes = [];
            foreach ($this->allroutes as $controller => $actions) {
                $routes = array_merge($routes, $actions);
            }
            return $routes;
        }
    }


    public function getControllerRoutes($cachedRoutes)
    {
        foreach ($cachedRoutes as $controller => $routes) {
            if (str_contains($controller, $this->namespace)) {
                try {
                    $controllerFilePath = $this->controllerFilePath($controller);
                    if (file_exists($controllerFilePath)) {
                        $controllerRoutes = require_once $controllerFilePath;
                        $diff = false;
                        if (!is_bool($controllerRoutes)) {
                            foreach ($controllerRoutes as $key => $value) {
                                if (!isset($routes[$key])) {
                                    $diff = true;
                                    unset($controllerRoutes[$key]);
                                }
                            }
                            foreach ($routes as $key => $value) {
                                if (!isset($controllerRoutes[$key])) {
                                    $diff = true;
                                    $controllerRoutes[$key] = $value;
                                }
                            }
                        }
                        if ($diff) {
                            $this->writeStart($controllerFilePath);
                            $this->writeEnd($controllerFilePath, $controllerRoutes);
                        }
                        $cachedRoutes[$controller] = $controllerRoutes;
                    }
                } catch (\Throwable $th) {
                    dd($th->getMessage(), "Controller actionlarni php fayldan o'qiganda");
                }
            }
        }
        return $cachedRoutes;
    }

    public function getRoutesFromLaravelCache()
    {
        $routes = Route::getRoutes()->getRoutes();
        $data = [];
        foreach ($routes as $r) {
            try {
                $controller = $r->getController();
                $controllerName = $controller::class;
            } catch (\Throwable $th) {
                $controllerName = "WEB";
            }
            if (!isset($data[$controllerName])) {
                $data[$controllerName] = [];
            }
            $name = $r->getName();
            $method = empty($r->methods()) ? 'get' : $r->methods()[0];

            $prefixes = explode('/', $r->getPrefix());
            $baseFolder = count($prefixes) > 1 ? $prefixes[1] : $prefixes[0];
            $route = [];
            $route['uri'] = $r->uri();
            $route['name'] = $r->getName();
            $route['prefix'] = $r->getPrefix();
            $route['folder'] = $this->makePath($baseFolder, $this->sliceControllerName($controllerName));
            $route['action'] = $r->getActionMethod();
            $route['method'] = $method;
            if (str_contains($route['uri'], '{')) {
                $url = $route['uri'];
                $index = strpos($url, '{');
                while ($index) {
                    $url = substr($url, $index + 1);
                    $i = strpos($url, '}');
                    $param = substr($url, 0, $i);
                    $params[] = $param;
                    $url = substr($url, $i + 1);
                    $index = strpos($url, '{');
                }
            }
            if (!empty($params)) {
                $route['parameters']['params'] = [];
                $route['parameters']['infos'] = [];
                foreach ($params as $p) {
                    $route['parameters']['params'][$p] = 1;
                    $route['parameters']['infos'][$p] = ['in' => 'path', 'value' => 1];
                }
            }
            $route['parameters'] = ['params' => [], 'infos' => []];
            if (!in_array($method, ['GET', 'DELETE'])) {
                $route['data'] = $this->authData($controllerName);
            }
            $route['tags'] = [$this->sliceControllerName($controllerName)];
            $route['description'] = '';
            $route['content-type'] = 'application/json';
            $route['auth'] = true;
            $data[$controllerName][$name] = $route;
        }
        return $data;
    }

    public function authData($controllerName)
    {
        return $controllerName == $this->namespace . "AuthController" ?
            [
                "grant_type" => "password",
                "client_secret" => "Mt57LfRyUwwWIuKfSXnNzQAeWxQY0JFNerkrLymd",
                "client_id" => 2,
                "username" => "AN0657",
                "password" => "Adm@0657"
            ] : [];
    }
}
