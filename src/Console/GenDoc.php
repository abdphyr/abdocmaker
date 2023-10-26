<?php

namespace Abd\Docmaker\Console;

use Abd\Docmaker\Services\MakeRequestServise;
use Abd\Docmaker\Traits\Bootstrap;
use Abd\Docmaker\Traits\ControllerTrait;
use Abd\Docmaker\Traits\OptionsTrait;
use Abd\Docmaker\Traits\ParseValueTrait;
use Abd\Docmaker\Traits\PathsTrait;
use Abd\Docmaker\Traits\RoutesTrait;
use Abd\Docmaker\Traits\WriteTrait;
use Illuminate\Console\Command;
use Symfony\Component\VarDumper\VarDumper;


class GenDoc extends Command
{
    use Bootstrap, OptionsTrait, ParseValueTrait, PathsTrait;
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

    protected $prefixes = [];

    protected $authData = [];

    public function __construct(protected MakeRequestServise $makeRequestService)
    {
        $this->controllersPath = public_path($this->controllersPath);
        $this->docsPath = public_path($this->docsPath);
        parent::__construct();
    }

    public function handle()
    {
        try {
            $this->bootstrap();
            if ($this->clear()) {
                if ($controller = $this->controller()) {
                    $path = $this->controllerFilePath($controller, false);
                    $routes = $this->getRoutes(withCreate: false);
                    if (!empty($routes)) {
                        clrmdir($this->docsPath . '/' . array_values($routes)[0]['folder']);
                    }
                    if (file_exists($path)) {
                        unlink($path);
                    } else {
                        $this->info("$controller api document files not found");
                        exit;
                    }
                    $this->info("$controller api document files deleted successfully");
                }
            } else {
                $this->makeRequestService->setApp($this->laravel);
                $routes = $this->getRoutes(withCreate: true);
                if (!empty($routes)) {
                    foreach ($routes as $route) {
                        $route = $this->prepareRoute($route);
                        $response = $this->makeRequestService->resolve($route);
                        $this->writeMainDocFile($route);
                        $this->writeToDocFile($route, $response);
                        $this->info("JSON documentation generated successfully");
                    }
                }
            }
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }

    public function dumper($info)
    {
        VarDumper::dump($info);
    }
}
