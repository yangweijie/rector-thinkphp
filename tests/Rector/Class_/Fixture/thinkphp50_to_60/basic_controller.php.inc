<?php

namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function show()
    {
        return $this->fetch('show');
    }

    public function create()
    {
        return $this->fetch();
    }
}

?>
-----
<?php

namespace app\controller;

use think\Controller;

class Index extends Controller
{
    public function index(): string
    {
        return $this->fetch();
    }

    public function show(): string
    {
        return $this->fetch('show');
    }

    public function create(): string
    {
        return $this->fetch();
    }
}

?>
