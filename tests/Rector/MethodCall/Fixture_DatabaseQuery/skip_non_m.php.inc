<?php

class UserController
{
    public function index()
    {
        $user = db('User')->where('name', 'test')->find();
        $model = D('User')->where(['id' => 1])->find();
        $result = $this->where(['status' => 1])->select();
    }
}

?>
-----
<?php

class UserController
{
    public function index()
    {
        $user = db('User')->where('name', 'test')->find();
        $model = D('User')->where(['id' => 1])->find();
        $result = $this->where(['status' => 1])->select();
    }
}

?>
