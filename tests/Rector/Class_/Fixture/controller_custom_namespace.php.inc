<?php

class UserController extends Controller
{
    public function index()
    {
        $this->display();
    }
    
    public function profile()
    {
        $user = $this->getUser();
        $this->assign('user', $user);
        $this->display('User:profile');
    }
}

?>
-----
<?php

namespace app\index\controller;

use think\Controller;

class User extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
    
    public function profile()
    {
        $user = $this->getUser();
        $this->assign('user', $user);
        return $this->fetch('User/profile');
    }
}

?>
