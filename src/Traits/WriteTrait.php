<?php

namespace Abd\Docmaker\Traits;

use Illuminate\Support\Str;
use Closure;


trait WriteTrait
{
    public function writeDoc($route, $response, $routes)
    {
        $data = json_decode($response->getContent(), true);
        $document[strtolower($route['method'])] = $this->makeEndpoint($route, $data);
        $path = $this->docFilePath($route);
        $data = Str::remove('\\', json_encode($document));
        $this->writeMainDocFile();
        file_put_contents($path, $data);
    }

    public function makeEndpoint($route, $data)
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
            '200' => [
                'description' => 'Success',
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

    public function writeMainDocFile()
    {
        $path = $this->docsPath . '/' . $this->mainDocFile . '.json';
        if (!file_exists($path)) {
            file_put_contents($path, file_get_contents(dirname(__DIR__) . '/template.json'));
        }
        return $path;
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

    public function writeControllerRoutes($sliceRoutes)
    {
        try {
            foreach ($sliceRoutes as $controller => $routes) {
                if (str_contains($controller, $this->namespace)) {
                    $controllerFilePath = $this->controllerFilePath($controller);
                    if (!file_exists($controllerFilePath)) {
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
