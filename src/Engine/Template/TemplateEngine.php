<?php

namespace Boiler\Core\Engine\Template;

use App\Config\ViewsConfig;

class TemplateEngine
{

    public static $content = [];

    public static $ext;

    public function __construct($ext = null)
    {
        self::$ext = $ext;
    }

    public function content($fileContent, $content = [])
    {
        self::$content = $content;
        $fcontent = static::editFile($fileContent, $content);

        ob_start();
        eval("?> " . $fcontent);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    static public function auth($fcontent)
    {
        $fcontent = preg_replace("/auth\@user\(\)\-\>(.*)\}\}/", 'TemplateEngine::auth_props("$1")}}', $fcontent);
        $fcontent = preg_replace("/auth\@user\(\)\-\>(.*)\)/", 'TemplateEngine::auth_props("$1"))', $fcontent);
        return $fcontent;
    }

    static public function auth_props($key)
    {
        if (isset(auth()->$key)) {
            return auth()->$key;
        }
    }


    static public function basic($fcontent)
    {
        # framework initials
        $fcontent = preg_replace("/@php/", '<?php', $fcontent);
        $fcontent = preg_replace("/@endphp/", '?>', $fcontent);
        $fcontent = preg_replace("/@\{\{/", '<?php echo ', $fcontent);
        $fcontent = preg_replace("/\{\{/", '<?php ', $fcontent);
        $fcontent = preg_replace("/\}\}/", '; ?>', $fcontent);

        return $fcontent;
    }

    static public function ConditionalStatement($fcontent)
    {

        $fcontent = preg_replace("/\@if\((.*)\)/", '<?php if($1){ ?>', $fcontent);
        $fcontent = preg_replace("/\@elif\((.*)\)/", '<?php } elseif($1) { ?>', $fcontent);
        $fcontent = preg_replace("/\@else/", '<?php } else { ?>', $fcontent);
        $fcontent = preg_replace("/\@endif/", '<?php } ?>', $fcontent);

        return $fcontent;
    }

    static public function editFile($fileContent, $content = null)
    {

        $fcontent = $fileContent;
        // $fcontent = TemplateEngine::htmlSymbolicCharacters($fcontent);
        $fcontent = preg_replace("/@\{\{(.*)load (.*)\}\}/", '<?php echo \Boiler\Core\Engine\Template\TemplateEngine::loadFile(($2), $content); ?>', $fcontent);
        $fcontent = static::auth($fcontent);
        $fcontent = static::sessions($fcontent);
        $fcontent = static::keys($fcontent, $content);
        $fcontent = static::FunctionStatement($fcontent);
        $fcontent = static::ConditionalStatement($fcontent);
        $fcontent = static::ToLoopContents($fcontent);
        $fcontent = static::basic($fcontent);
        $fcontent = static::emptyParameter($fcontent);

        return $fcontent;
    }

    // static function htmlSymbolicCharacters($fcontent)
    // {
    //     $fcontent = str_replace("$", "&dollar;", $fcontent);
    //     return $fcontent;
    // }

    static function emptyParameter($fcontent)
    {
        $fcontent = preg_replace("/(.*)if\((.*)\~(.*)\~(.*)\)/", '$1 if(false) ', $fcontent);
        $fcontent = preg_replace("/(.*)for\((.*)\~(.*)\~(.*)\)/", '$1 for($i = 0; $i <= 0; $i++) ', $fcontent);
        $fcontent = preg_replace("/(.*)foreach\((.*)\~(.*)\~(.*)\)/", '$1 foreach(array() as $no_value) ', $fcontent);
        $fcontent = preg_replace("/(.*)\~(.*)\~(.*)/", "$1" . "false" . "$3", $fcontent);
        return $fcontent;
    }

    public function extendLayout($fcontent, $viewPath, $ext)
    {

        if (preg_match("/@\{\{extends/", $fcontent)) {

            $layout = substr($fcontent, strpos($fcontent, "@{{extends "), strpos($fcontent, "}}"));
            $layout = str_replace("@{{extends \"", "", $layout);
            $cleaned = str_replace("\"", "", trim($layout, " "));
            $layoutPath = './../' . $viewPath . "/" . $cleaned . "." . "$ext";

            $layout = file_get_contents($layoutPath);


            $fcontent = preg_replace("/@\{\{extends (.*)\}\}/", "", $fcontent);

            $scripts = "";
            if (strpos($fcontent, "@startScripts")) {
                $scripts = preg_replace("/(.*)@startScripts(.*)@endScripts(.*)/s", "$2", $fcontent);
            }

            $styles = "";
            if (strpos($fcontent, "@startStyles")) {
                $styles = preg_replace("/(.*)@startStyles(.*)@endStyles(.*)/s", "$2", $fcontent);
            }


            $fcontent = preg_replace("/(.*)@startStyles(.*)@endStyles(.*)/s", "$1"."$3", $fcontent);
            $fcontent = preg_replace("/(.*)@startScripts(.*)@endScripts(.*)/s", "$1"."$3", $fcontent);
            
            $layout = preg_replace("/@\{\{(.*)content(.*)\}\}/", $fcontent, $layout);
            $layout = preg_replace("/@\{\{(.*)renderStyles(.*)\}\}/", $styles, $layout);
            $layout = preg_replace("/@\{\{(.*)renderScripts(.*)\}\}/", $scripts, $layout);

            return $layout;
        }

        return $fcontent;
    }


    public function renderScript()
    {
    }

    static public function FunctionStatement($fcontent)
    {
        $fcontent = preg_replace("/\@func (.*)\((.*)\)/", '<?php function $1($2){ ', $fcontent);
        $fcontent = preg_replace("/\@endfunc/", ' } ?>', $fcontent);

        return $fcontent;
    }

    static function keys($fcontent, $content = null)
    {
        if (!is_null($content)) {
            foreach ($content as $key => $value) {
                if (!is_array($value)) {
                    $fcontent = preg_replace("/(.*)\~" . $key . "\~(.*)/", '$1$content["' . $key . '"]$2', $fcontent);
                }

                if (is_array($value)) {
                    $fcontent = preg_replace("/\~" . $key . "\~/", '$content["' . $key . '"]', $fcontent);
                }
            }
        }

        return $fcontent;
    }

    static public function loadFile($filename, $content)
    {
        $file_path = __DIR__ . "/../../../../../../" . ViewsConfig::$views_path . "/" . $filename . "." . self::$ext;
        $fcontent = file_get_contents($file_path);
        $fcontent = static::editFile($fcontent, $content);
        $fcontent = eval("?>" . $fcontent . "");
        return $fcontent;
    }

    static function sessions($fcontent)
    {
        foreach ($_SESSION as $key => $value) {
            if (!is_array($value)) {
                $fcontent = preg_replace("/(.*)\~" . $key . "\~(.*)/", '$1$_SESSION["' . $key . '"]$2', $fcontent);
            }

            if (is_array($value)) {
                $fcontent = preg_replace("/\~" . $key . "\~/", '$_SESSION["' . $key . '"]', $fcontent);
            }
        }
        return $fcontent;
    }

    static public function ToLoopContents($fcontent)
    {

        $fcontent = preg_replace("/\@foreach\((.*)\)/", '<?php foreach($1){ ?>', $fcontent);
        $fcontent = preg_replace("/\@endforeach/", '<?php } ?>', $fcontent);

        $fcontent = preg_replace("/\@for\((.*)\)/", '<?php for($1){ ?>', $fcontent);
        $fcontent = preg_replace("/\@endfor/", '<?php } ?>', $fcontent);

        return $fcontent;
    }
}
