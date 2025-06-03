<?php

use think\App;
use think\Cache;
use think\Config;
use think\Route;

class IndexController
{
    public function index()
    {
        $version = App::version();
        $config = Config::get('app_debug');
        Cache::set('key', 'value');
        Route::get('hello', 'index/hello');
    }
}

?>
-----
<?php

use think\facade\App;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Route;

class IndexController
{
    public function index()
    {
        $version = App::version();
        $config = Config::get('app_debug');
        Cache::set('key', 'value');
        Route::get('hello', 'index/hello');
    }
}

?>
