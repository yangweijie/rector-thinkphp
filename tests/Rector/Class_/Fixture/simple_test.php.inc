<?php

class TestController extends Controller
{
    public function index()
    {
        $this->display();
    }
}

?>
-----
<?php

namespace app\index\controller;

use think\Controller;
class Test extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
}

?>
