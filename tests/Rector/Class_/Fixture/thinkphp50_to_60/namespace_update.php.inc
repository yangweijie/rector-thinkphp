<?php

namespace app\admin\controller;

use think\Controller;

class User extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function profile()
    {
        return $this->fetch();
    }
}

?>
-----
<?php

namespace app\controller;

use think\Controller;

class User extends Controller
{
    public function index(): string
    {
        return $this->fetch();
    }

    public function profile(): string
    {
        return $this->fetch();
    }
}

?>
