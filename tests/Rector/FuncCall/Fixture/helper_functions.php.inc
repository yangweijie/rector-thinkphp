<?php

class TestController
{
    public function index()
    {
        $config = C('database');
        $user = M('User')->find(1);
        $model = D('User');
        $name = I('get.name');
        $lang = L('hello');
        $url = U('Index/index');
        $cache = S('key');
        $debug = G('start', 'end');
        $exception = E('Error message');
    }
}

?>
-----
<?php

class TestController
{
    public function index()
    {
        $config = config('database');
        $user = db('User')->find(1);
        $model = model('User');
        $name = input('get.name');
        $lang = lang('hello');
        $url = url('Index/index');
        $cache = cache('key');
        $debug = debug('start', 'end');
        $exception = exception('Error message');
    }
}

?>
