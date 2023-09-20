<?php

namespace Abd\Docmaker\Traits;

trait Bootstrap
{
    protected function bootstrap()
    {
        try {
            $this->getDir(public_path('docs/'));
            $path = $this->docsPath . '/' . $this->mainDocFile . '.json';
            if (!file_exists($path)) {
                $this->writeJs();
                $this->writeBlade();
                $this->writeCss();
            }
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }

    protected function writeCss()
    {
        $css = file_get_contents(dirname(__DIR__) . '/assets/css.css');
        file_put_contents($this->getDir(public_path('css')) . '/' . $this->mainDocFile . '-api-docs.css', $css);
    }

    protected function writeBlade()
    {
        $html = file_get_contents(dirname(__DIR__) . '/assets/html.blade.php');
        $html = str_replace('maindoc', $this->mainDocFile, $html);
        file_put_contents($this->getDir(resource_path('views/docs')) . '/' . $this->mainDocFile . '-api-docs.blade.php', $html);
    }

    protected function writeJs()
    {
        $js = file_get_contents(dirname(__DIR__) . '/assets/js.js');
        $js = str_replace('maindoc', $this->mainDocFile, $js);
        file_put_contents($this->getDir(public_path('js')) . '/' . $this->mainDocFile . '-api-docs.js', $js);
    }

    protected function getDir($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir);
        }
        return $dir;
    }
}
