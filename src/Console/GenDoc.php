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

    protected $controllersPath;

    protected $docsPath;

    protected $actions = [];

    protected $allroutes = [];

    public function __construct(protected MakeRequestServise $makeRequestService)
    {
        $this->controllersPath = public_path("/docs/routes");
        $this->docsPath = public_path("/docs");
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
            foreach ($routes as $route) {
                $route = $this->prepareRoute($route);
                $response = $this->makeRequestService->resolve($route);
                $this->dumper(json_decode($response->getContent()));
                $this->newLine(3);
                $this->writeDoc($route, $response);
                $this->info("JSON documentation generated successfully");
            }
        }
    }    

    public function dumper($response)
    {
        VarDumper::dump($response);
    }    
}
