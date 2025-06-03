<?php

class TestController
{
    public function index()
    {
        $debug = config('app_debug');
        $trace = config('app_trace');
        $template = config('template');
        $replace = config('view_replace_str');
    }
}

?>
-----
<?php

class TestController
{
    public function index()
    {
        $debug = config('app.app_debug');
        $trace = config('app.app_trace');
        $template = config('template');
        $replace = config('template.tpl_replace_string');
    }
}

?>
