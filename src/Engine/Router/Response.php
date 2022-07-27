<?php

namespace Boiler\Core\Engine\Router;

use App\Config\ViewsConfig;
use Boiler\Core\Engine\Router\Request;
use Boiler\Core\Engine\Template\TemplateEngine;
use Boiler\Core\FileSystem\Fs;
use Exception;


class Response
{

    public static function setResponseHeader($status, $json = false, $message = '')
    {
        if ($json == true) {
            header('Content-Type: application/json; charset=utf-8');
        }

        header("HTTP/1.1 " . $status . ' ' . $message);
    }

    public static function responseFormat()
    {

        $method = strtolower($_SERVER["REQUEST_METHOD"]);

        $request = new Request($method);
        $headers = $request->headers();

        if (isset($headers["Accept"])) {
            return $headers["Accept"];
        }

        return "*/*";
    }

    public static function get_view_path($filename)
    {

        $view_path = ViewsConfig::$views_path;

        $extension = "fish.php";
        $full_path = "../" . $view_path . "/" . $filename . "." . $extension;

        return [
            "fullpath" => $full_path,
            "viewpath" => $view_path,
            "extension" => $extension,
            "viewfile" => $filename
        ];
    }

    public static function view($view_file, $content = null, $status = 200)
    {

        Response::setResponseHeader($status);

        $path = Response::get_view_path($view_file);

        return static::absoluteView($path, $content, $status);
    }

    public static function absoluteView($path, $content = null, $status = 200)
    {
        Response::setResponseHeader($status);

        $view_file = $path['viewfile'] ?? null;
        $view_path = $path["viewpath"] ?? null;
        $extension = $path["extension"] ?? null;

        $full_path = $path["fullpath"] ?? concat([concat([$view_path, $view_file], '/'), $extension], '.');

        if (Fs::exists($full_path)) {
            $fcontent = file_get_contents($full_path);

            $template = new TemplateEngine($extension);
            $fcontent = $template->extendLayout($fcontent, $view_path, $extension);
            $fcontent = $template->render($fcontent, $content);
            return $template;
        } else {
            throw new Exception($view_file . " does not exists");
        }
    }

    public static function content($text, $status = 200)
    {
        Response::setResponseHeader($status);
        echo $text;
    }

    public static function json($data, $status = 200)
    {
        Response::setResponseHeader($status, true);
        echo json_encode($data);
    }

    public static function redirect($location)
    {
        $location = trim($location, "/");
        $location = "/" . $location;
        return header("location:" . $location);
    }

    public static function redirectToHost($location)
    {
        return header("location:" . $location);
    }

    public static function unhandledPost()
    {
        return Response::view("core/errors/unhadledPost");
    }

    public static function error404()
    {
        if (Response::responseFormat() == "application/json") {

            return Response::json(['success' => false, 'error' => ['message' => '404 | Page Not Found!']], 404);
        }

        return Response::view("errors/404");
    }

    public static function mailPage($view_file, $data = null)
    {

        $path = Response::get_view_path($view_file);

        $full_path = $path["fullpath"];
        $view_path = $path["viewpath"];
        $extension = $path["extension"];

        if (Fs::exists($full_path)) {

            $fcontent = file_get_contents($full_path);

            $template = new TemplateEngine($extension);
            $fcontent = $template->extendLayout($fcontent, $view_path, $extension);
            $fcontent = $template->content($fcontent, $data);

            return $fcontent;
        } else {
            throw new Exception($view_file . " does not exists");
        }
    }
}
