<?php

class IndexController extends Controller
{
    public function index()
    {
        $this->display();
    }
    
    public function list()
    {
        $data = array('users' => $this->getUserList());
        $this->assign('data', $data);
        $this->display();
    }
}

?>
-----
<?php

namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function list()
    {
        $data = array('users' => $this->getUserList());
        $this->assign('data', $data);
        return $this->fetch();
    }
}

?>
