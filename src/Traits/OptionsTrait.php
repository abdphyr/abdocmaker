<?php

namespace Abd\Docmaker\Traits;

trait OptionsTrait
{
    public function index()
    {
        return !$this->option('i');
    }

    public function show()
    {
        return !$this->option('sh');
    }

    public function store()
    {
        return !$this->option('s');
    }

    public function update()
    {
        return !$this->option('u');
    }

    public function destroy()
    {
        return !$this->option('d');
    }

    public function other()
    {
        return !$this->option('o');
    }

    public function clear()
    {
        return !$this->option('clear');
    }

    public function controllerActions()
    {
        return $this->option("acts") ? explode(',', $this->option("acts")) : null;
    }
}
