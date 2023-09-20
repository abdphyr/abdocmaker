<?php

namespace Abd\Docmaker\Traits;

use Illuminate\Support\Str;
use Closure;


trait WriteTrait
{
    public function makeEndpoint($route, $data, $status, $statusText)
    {
        $body = [];
        $body['tags'] = getterValue($route, 'tags');
        $body['description'] = getterValue($route, 'description');
        if (!empty($route['parameters'])) {
            $body['parameters'] = [];
            foreach (getterArray($route, 'parameters', 'infos') as $key => $value) {
                $body['parameters'][] = [
                    'name' => $key,
                    'in' => getterValue($value, 'in'),
                    'required' => false,
                    'schema' => [
                        'type' => $this->getType(getterValue($value, 'value')),
                        'example' => getterValue($value, 'value')
                    ]
                ];
            }
        }
        if (!empty($route['headers'])) {
            if (!isset($body['parameters'])) {
                $body['parameters'] = [];
            }
            foreach (getterArray($route, 'headers') as $key => $value) {
                if ($key === "Authorization") continue;
                $body['parameters'][] = [
                    'name' => $key,
                    'in' => 'header',
                    'required' => false,
                    'schema' => [
                        'type' => $this->getType($value),
                        'example' => $value
                    ]
                ];
            }
        }
        if (isset($route['data']) && !empty($route['data'])) {
            foreach ($route['data'] as $key => $value) {
                if ($value instanceof Closure) {
                    $route['data'][$key] = $value();
                }
            }
            $body['requestBody'] = [
                'description' => getterValue($route, 'description'),
                'content' => [
                    $route['content-type'] => [
                        'schema' => $this->parser($route['data'])
                    ]
                ]
            ];
        }
        $body['responses'] = [
            "$status" => [
                'description' => "$statusText",
                'content' => [
                    $route['content-type'] => [
                        'schema' => $this->parser($data)
                    ]
                ]
            ]
        ];
        if ($route['auth']) {
            $body['security'] = [
                [
                    'bearerAuth' => []
                ]
            ];
        }
        return $body;
    }

    public function writeToDocFile($route, $response)
    {
        $path = $this->docFilePath($route)['path'];
        $method = strtolower($route['method']);
        $status = $response->status();
        $data = json_decode($response->getContent(), true);
        if($status != 200) {
            $this->dumper($data);
        }
        $document = [];
        $endpoint = $this->makeEndpoint($route, $data, $status, $response->statusText());
        if (file_exists($path)) {
            $filedata = json_decode(file_get_contents($path), true);
            foreach ($filedata as $key => $value) {
                if ($key == $method) {
                    $responses = $filedata[$method]['responses'];
                    $responses["$status"] = $endpoint['responses']["$status"];
                    $endpoint['responses'] = $responses;
                    $document[$method] = $endpoint;
                } else {
                    $document[$key] = $value;
                }
            }
            if (!isset($document[$method])) {
                $document[$method] = $endpoint;
            }
        } else {
            $document[$method] = $endpoint;
        }
        $data = Str::remove('\\', json_encode($document));
        file_put_contents($path, $data, JSON_PRETTY_PRINT);
    }

    public function writeMainDocFile($route)
    {
        $action = $this->docFilePath($route)['action'];
        $endpoint = str_replace('api', '', $route['url']);
        $baseDocPath = $this->mainDocFilePath();
        $baseDocValue = json_decode(file_get_contents($baseDocPath), true);
        if (!isset($baseDocValue['paths'][$endpoint])) {
            $baseDocValue['paths'][$endpoint] = [
                '$ref' => $route['folder'] . '/' . $action . '.json'
            ];
            file_put_contents($baseDocPath, Str::remove('\\', json_encode($baseDocValue)), JSON_PRETTY_PRINT);
        }
    }

    public function writeStart($path)
    {
        file_put_contents($path, "");
        return file_put_contents($path, "<?php \n return ");
    }

    public function writeEnd($path, $data)
    {
        return file_put_contents($path, var_export($data, true) . ";", FILE_APPEND);
    }

    public function writeControllerRoutes($sliceRoutes, $withCreate)
    {
        try {
            foreach ($sliceRoutes as $controller => $routes) {
                if (str_contains($controller, $this->namespace)) {
                    $controllerFilePath = $this->controllerFilePath($controller, $withCreate);
                    if ($withCreate && !file_exists($controllerFilePath)) {
                        $this->writeStart($controllerFilePath);
                        $this->writeEnd($controllerFilePath, $routes);
                    }
                }
            }
        } catch (\Throwable $th) {
            dd($th->getMessage(), "Controller actionlarni php faylga yozganda");
        }
    }
}
