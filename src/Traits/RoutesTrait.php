<?php

namespace Abd\Docmaker\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;


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
        $route['url'] = $route['uri'];
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

    public function getRoutes($withCreate)
    {
        $allCachedRoutes = $this->getRoutesFromLaravelCache();
        $this->allroutes = $this->getControllerRoutes($allCachedRoutes, $withCreate);
        if ($controller = $this->controller()) {
            if (isset($allCachedRoutes[$controller])) {
                $writable[$controller] = $allCachedRoutes[$controller];
                $writable[$this->namespace . "AuthController"] = $allCachedRoutes[$this->namespace . "AuthController"];
                $this->writeControllerRoutes($writable, $withCreate);
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
            $this->writeControllerRoutes($allCachedRoutes, $withCreate);
            $routes = [];
            foreach ($this->allroutes as $controller => $actions) {
                $routes = array_merge($routes, $actions);
            }
            return $routes;
        }
    }


    public function getControllerRoutes($cachedRoutes, $withCreate)
    {
        foreach ($cachedRoutes as $controller => $routes) {
            if (str_contains($controller, $this->namespace)) {
                try {
                    $controllerFilePath = $this->controllerFilePath($controller, $withCreate);
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
            if (!empty($this->prefixes) && !in_array($r->getPrefix(), $this->prefixes)) continue;
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
            $params = [];
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

            $route['parameters'] = ['params' => [], 'infos' => []];

            if (($route['action'] == 'index') || ($route['method'] == 'POST')) {
                unset($route['parameters']);
            }
            if (!empty($params)) {
                $route['parameters']['params'] = [];
                $route['parameters']['infos'] = [];
                foreach ($params as $p) {
                    $route['parameters']['params'][$p] = 1;
                    $route['parameters']['infos'][$p] = ['in' => 'path', 'value' => 1];
                }
            }
            if ($controllerName == $this->namespace . "AuthController") {
                $route['data'] = $this->authData;
            } else {
                if (!in_array($method, ['GET', 'DELETE'])) {
                    $rules = [];
                    $details = new  ReflectionMethod($controllerName, $route['action']);
                    $parameters = $details->getParameters();
                    foreach ($parameters as $p) {
                        if (!is_null($arg = $p->getType())) {
                            try {
                                $dto = $arg->getName();
                                $obj = new $dto();
                            } catch (\Throwable $th) {
                                dd($th->getMessage());
                            }
                            $rules = $obj->rules();
                            $rules = $this->summer($rules);
                        }
                    }
                    $route['data'] = $rules;
                }
            }
            $route['tags'] = [$this->sliceControllerName($controllerName)];
            $route['description'] = '';
            $route['content-type'] = 'application/json';
            $route['auth'] = true;
            $data[$controllerName][$name] = $route;
        }
        return $data;
    }

    private function summer(array $rules)
    {
        $result = [];
        foreach ($rules as $key => $value) {
            $keys = explode('.', $key);
            if (count($keys) == 1) {
                [$result, $key, $value] = $this->typer($result, $key, $value);
            }
            if (count($keys) == 2 && $keys[1] == '*') {
                if (isset($result[$keys[0]])) {
                    $data = [];
                    [$data, $key, $value] = $this->typer($data, $key, $value);
                    $result[$keys[0]][] = $data[$key];
                }
            }
            if (count($keys) == 3 && $keys[1] == '*') {
                if (!isset($result[$keys[0]][$keys[2]])) {
                    $data = [];
                    [$data, $key, $value] = $this->typer($data, $key, $value);
                    $result[$keys[0]][$keys[2]] = $data[$key];
                }
            }
        }
        return $result;
    }

    public function typer($result, $key, $value)
    {
        if (is_array($value)) {
            if (in_array('array', $value)) {
                $result[$key] = [];
            } else if (in_array('string', $value)) {
                $result[$key] = Str::random(10);
            } else if (in_array('integer', $value)) {
                $result[$key] = rand(100, 100000);
            } else if (in_array('bool', $value)) {
                $result[$key] = [true, false][rand(0, 1)];
            } else {
                $result[$key] = 1;
            }
        } else {
            if (str_contains($value, 'array')) {
                $result[$key] = [];
            } else if (str_contains($value, 'string')) {
                $result[$key] = Str::random(10);
            } else if (str_contains($value, 'integer')) {
                $result[$key] = rand(100, 100000);
            } else if (str_contains($value, 'bool')) {
                $result[$key] = [true, false][rand(0, 1)];
            } else {
                $result[$key] = 1;
            }
        }
        return [$result, $key, $value];
    }
}
