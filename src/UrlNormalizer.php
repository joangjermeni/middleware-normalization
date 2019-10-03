<?php
declare(strict_types=1);

namespace TestApp;

use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;


class UrlNormalizer
{
    private $normalizedUrl;
    private $previousUrl;
    private $scheme;
    private $host;
    private $port;
    private $path;
    private $urlComponents = array( 'scheme', 'host', 'port', 'path');
    private $redirect = true;
    private $response;

    public function __construct($url = null, ResponseInterface $response)
    {
        $this->previousUrl = $url;
        $this->setWebsiteUrl($url);
        $this->response = $response;
    }

    public function __invoke(): ResponseInterface
    {
        $this->scheme .= ':';
        $fullHost = '//' . $this->host;
        if ($this->port) {
            $fullHost .= ':' . $this->port;
        }
        // Normalize url path
        if ($this->path !== '/') {
            $this->path = $this->charactersToLowercase($this->path);
            $this->path = $this->removeMultipleSlashes($this->path);
            $this->path = $this->removeTrailingSlash($this->path);
            $this->path = $this->removeDotSegments($this->path);
        }
        $fullHost = $this->removeWWWLabel($fullHost);

        $this->setWebsiteUrl($this->scheme . $fullHost . $this->path);
        if ($this->redirect && $this->previousUrl != $this->normalizedUrl) {
            return Factory::getResponseFactory()->createResponse(301)
                ->withHeader('Location', (string) $this->getWebsiteUrl());
        }
        $response = $this->response->withHeader('Content-Type', 'text/html');
        $response->getBody()
            ->write('<html><head></head><body>This is the url after normalization process : <b>'.$this->getWebsiteUrl().'</b></body></html>');

        return $response;
    }

    public function getWebsiteUrl()
    {
        return $this->normalizedUrl;
    }

    public function setWebsiteUrl($url)
    {
        $this->normalizedUrl = $url;
        $urlComponentsValue = parse_url($this->normalizedUrl);
        // Update url properties
        foreach ($urlComponentsValue as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        // Add missing url components
        $emptyUrlComponents = array_diff(
            array_values($this->urlComponents),
            array_keys($urlComponentsValue)
        );
        // Add empty value to the missing url components
        foreach ($emptyUrlComponents as $key) {
            if (property_exists($this, $key)) {
                $this->$key = '';
            }
        }
        return true;
    }

    /*
     * Path normalization - return path with all alphabetic characters converted to lowercase
     */
    public function charactersToLowercase($path)
    {
        return strtolower($path);
    }

    /*
     * Path normalization - converts multiple slashes to one slash on each path segment
     */
    private function removeMultipleSlashes($path)
    {
        return preg_replace('/(\/)+/', '/', $path);
    }

    /*
     * Path normalization - remove trailing slash from the url path
     */
    public function removeTrailingSlash($path)
    {
        if (strlen($path) >= 1) {
            return rtrim($path, '/');
        }
        return $path;
    }

    /*
     * Path normalization - remove "www" label from hostname
     */
    public function removeWWWLabel($string)
    {
        $string = str_replace('www.', '', $string);
        return $string;
    }

    /*
     * Path normalization - remove dot segments
     */
    public function removeDotSegments($path)
    {
        $new_path = '';
        while (! empty($path)) {
            // A
            $pattern_a   = '!^(\.\./|\./)!x';
            $pattern_b_1 = '!^(/\./)!x';
            $pattern_b_2 = '!^(/\.)$!x';
            $pattern_c   = '!^(/\.\./|/\.\.)!x';
            $pattern_d   = '!^(\.|\.\.)$!x';
            $pattern_e   = '!(/*[^/]*)!x';
            if (preg_match($pattern_a, $path)) {
                // remove prefix from $path
                $path = preg_replace($pattern_a, '', $path);
            } elseif (preg_match($pattern_b_1, $path, $matches) || preg_match($pattern_b_2, $path, $matches)) {
                $path = preg_replace("!^" . $matches[1] . "!", '/', $path);
            } elseif (preg_match($pattern_c, $path, $matches)) {
                $path = preg_replace('!^' . preg_quote($matches[1], '!') . '!x', '/', $path);
                // remove the last segment and its preceding "/" (if any) from output buffer
                $new_path = preg_replace('!/([^/]+)$!x', '', $new_path);
            } elseif (preg_match($pattern_d, $path)) {
                $path = preg_replace($pattern_d, '', $path);
            } else {
                if (preg_match($pattern_e, $path, $matches)) {
                    $first_path_segment = $matches[1];
                    $path = preg_replace('/^' . preg_quote($first_path_segment, '/') . '/', '', $path, 1);
                    $new_path .= $first_path_segment;
                }
            }
        }
        return $new_path;
    }

    /**
     * Enabled or disable redirecting all together.
     */
    public function redirect(bool $redirect = true): self
    {
        $this->redirect = $redirect;
        return $this;
    }
}


