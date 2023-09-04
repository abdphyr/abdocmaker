<?php

namespace Abd\Docmaker\Console;

use Abd\Docmaker\Services\MakeRequestServise;
use Abd\Docmaker\Traits\ControllerTrait;
use Abd\Docmaker\Traits\OptionsTrait;
use Abd\Docmaker\Traits\ParseValueTrait;
use Abd\Docmaker\Traits\PathsTrait;
use Abd\Docmaker\Traits\RoutesTrait;
use Abd\Docmaker\Traits\WriteTrait;
use Illuminate\Console\Command;
use Symfony\Component\VarDumper\VarDumper;
use Illuminate\Support\Str;

class GenDoc extends Command
{
    use OptionsTrait, ParseValueTrait, PathsTrait;
    use WriteTrait, ControllerTrait, RoutesTrait;
    const COMMAND = "gen:doc ";
    const PARAM = "{--c= : The name of the controller} ";
    const INDEX = "{--i=1 : index action method} ";
    const SHOW = "{--sh=1 : show action method} ";
    const STORE = "{--s=1 : store action method} ";
    const UPDATE = "{--u=1 : uupdate action method} ";
    const DESTROY = "{--d=1 : destroy action method} ";
    const OTHER = "{--o=1 : other action method} ";
    const ACTIONS = "{--acts= : controller action methods}";
    const CLEAR = "{--clear=1 : clear cache and save to cache routes} ";


    protected $signature = self::COMMAND . self::PARAM . self::INDEX . self::SHOW . self::STORE . self::UPDATE . self::DESTROY . self::OTHER . self::ACTIONS . self::CLEAR;

    protected $description = 'Create swagger document for given controller';

    protected $namespace = 'App\\Http\\Controllers\\';

    protected $controllersPath = "/docs/routes";

    protected $docsPath = "/docs";

    protected $mainDocFile = 'master';

    private $actions = [];

    private $allroutes = [];

    public function __construct(protected MakeRequestServise $makeRequestService)
    {
        $this->controllersPath = public_path($this->controllersPath);
        $this->docsPath = public_path($this->docsPath);
        parent::__construct();
    }

    public function handle()
    {
        if ($this->clear()) {
            clrmdir($this->controllersPath);
            clrmdir($this->docsPath);
        } else {
            $this->makeRequestService->setApp($this->laravel);
            $routes = $this->getRoutes();
            $baseDocPath = $this->writeMainDocFile();
            $baseDocValue = json_decode(file_get_contents($baseDocPath), true);
            if (!empty($routes)) {
                foreach ($routes as $route) {
                    $route = $this->prepareRoute($route);
                    $response = $this->makeRequestService->resolve($route);
                    $data = json_decode($response->getContent(), true);
                    $document = [];
                    $document[strtolower($route['method'])] = $this->makeEndpoint($route, $data);
                    [$path, $action] = $this->docFilePath($route);
                    if (file_exists($path)) {
                        $filedata = json_decode(file_get_contents($path), true);
                        foreach ($filedata as $key => $value) {
                            if(strtolower($route['method']) != $key) {
                                $document[$key] = $value;
                            }
                        }
                    }
                    $data = Str::remove('\\', json_encode($document));
                    $endpoint = str_replace('api', '', $route['url']);
                    if (!isset($baseDocValue['paths'][$endpoint])) {
                        $baseDocValue['paths'][$endpoint] = [
                            '$ref' => $route['folder'] . '/' . $action . '.json'
                        ];
                        file_put_contents($baseDocPath, Str::remove('\\', json_encode($baseDocValue)));
                    }
                    file_put_contents($path, $data);
                    $this->info("JSON documentation generated successfully");
                }
            }
        }
    }

    public function dumper($response)
    {
        VarDumper::dump($response);
    }
}
