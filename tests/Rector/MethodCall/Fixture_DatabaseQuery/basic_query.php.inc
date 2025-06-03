<?php

class UserController
{
    public function index()
    {
        $user = M('User')->where(['name' => 'thinkphp'])->find();
        $users = M('User')->where(['status' => 1])->select();
        $count = M('User')->where(['active' => true])->count();
    }
}

?>
-----
<?php

class UserController
{
    public function index()
    {
        $user = db('User')->where('name', 'thinkphp')->find();
        $users = db('User')->where('status', 1)->select();
        $count = db('User')->where('active', true)->count();
    }
}

?>
