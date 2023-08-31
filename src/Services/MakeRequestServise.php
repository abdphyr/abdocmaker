<?php

namespace Abd\Docmaker\Services;


use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\VarDumper\VarDumper;

class MakeRequestServise
{
    protected $app;

    public function setApp($application)
    {
        $this->app = $application;
    }

    protected function transformHeadersToServerVars(array $headers)
    {
        return collect(array_merge([], $headers))->mapWithKeys(function ($value, $name) {
            $name = strtr(strtoupper($name), '-', '_');
            return [$this->formatServerHeaderKey($name) => $value];
        })->all();
    }

    protected function formatServerHeaderKey($name)
    {
        if (!Str::startsWith($name, 'HTTP_') && $name !== 'CONTENT_TYPE' && $name !== 'REMOTE_ADDR') {
            return 'HTTP_' . $name;
        }
        return $name;
    }

    protected function extractFilesFromDataArray(&$data)
    {
        $files = [];
        foreach ($data as $key => $value) {
            if ($value instanceof SymfonyUploadedFile) {
                $files[$key] = $value;
                unset($data[$key]);
            }
            if (is_array($value)) {
                $files[$key] = $this->extractFilesFromDataArray($value);
                $data[$key] = $value;
            }
        }
        return $files;
    }

    public function request($method, $uri, $parameters = [], array $data = [], array $headers = [], $contentType)
    {
        $files = $this->extractFilesFromDataArray($data);
        $content = json_encode($data);
        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => $contentType,
            'Accept' => $contentType,
        ], $headers);
        return $this->call(
            $method,
            $uri,
            $parameters,
            $this->prepareCookiesForJsonRequest(),
            $files,
            $this->transformHeadersToServerVars($headers),
            $content
        );
    }

    public function resolve($route)
    {
        $check = $this->optional($route);
        $checkParam = $this->optional($check('parameters'));
        $response = $this->request(
            method: $route['method'],
            uri: $route['uri'],
            data: $check('data'),
            parameters: $checkParam('params'),
            headers: $check('headers'),
            contentType: $route['content-type']
        );
        if ($response->isOk()) {
            return $response;
        } else {
            VarDumper::dump("Xatooooo",json_decode($response->getContent()));
            return $response;
        }
    }

    public function resolveToken($loginRoute)
    {
        return $this->resolve($loginRoute)->getData()?->access_token;
    }

    public function optional($route)
    {
        return function ($property) use ($route) {
            return isset($route[$property]) ? $route[$property] : [];
        };
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $kernel = $this->app->make(HttpKernel::class);
        $files = array_merge($files, $this->extractFilesFromDataArray($parameters));
        $symfonyRequest = SymfonyRequest::create(
            uri: $this->prepareUrlForRequest($uri),
            method: $method,
            parameters: $parameters,
            cookies: $cookies,
            files: $files,
            server: array_replace([], $server),
            content: $content
        );

        $response = $kernel->handle(
            $request = Request::createFromBase($symfonyRequest)
        );
        $kernel->terminate($request, $response);

        // if ($this->followRedirects) {
        //     $response = $this->followRedirects($response);
        // }
        return $response;
    }

    protected function prepareUrlForRequest($uri)
    {
        if (Str::startsWith($uri, '/')) {
            $uri = substr($uri, 1);
        }
        return trim(url($uri), '/');
    }

    protected function prepareCookiesForJsonRequest()
    {
        return [];
    }

    // protected function prepareCookiesForRequest()
    // {
    //     if (! $this->encryptCookies) {
    //         return array_merge($this->defaultCookies, $this->unencryptedCookies);
    //     }

    //     return collect($this->defaultCookies)->map(function ($value, $key) {
    //         return encrypt(CookieValuePrefix::create($key, app('encrypter')->getKey()).$value, false);
    //     })->merge($this->unencryptedCookies)->all();
    // }
}
