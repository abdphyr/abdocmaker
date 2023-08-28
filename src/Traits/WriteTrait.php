<?php
namespace Abd\Docmaker\Traits;

use Illuminate\Support\Str;

trait WriteTrait
{
    public function writeDoc($route, $response)
    {
        $data = json_decode($response->getContent(), true);
        $resschema = $this->parser($data);
        $body['tags'] = getter($route, 'tags');
        $body['description'] = getter($route, 'description');
        if (!empty($route['parameters'])) {
            $body['parameters'] = [];
            foreach (getterArray($route, 'parameters', 'infos') as $key => $value) {
                $body['parameters'][] = [
                    'name' => $key,
                    'in' => getter($value, 'in'),
                    'required' => false,
                    'schema' => [
                        'type' => $this->getType(getter($value, 'value')),
                        'example' => getter($value, 'value')
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
                if (is_callable($value)) {
                    $route['data'][$key] = $value();
                }
            }
            $body['requestBody'] = [
                'description' => getter($route, 'description'),
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
                        'schema' => $resschema
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
        $document[strtolower($route['method'])] = $body;
        $path = $this->docFilePath($route);
        $data = Str::remove('\\', json_encode($document));
        file_put_contents($path, $data);
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
