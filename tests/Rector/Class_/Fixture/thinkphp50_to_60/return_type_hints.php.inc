<?php

namespace app\index\controller;

use think\Controller;

class Product extends Controller
{
    public function list()
    {
        return $this->fetch();
    }

    public function detail($id)
    {
        return $this->fetch();
    }

    public function search()
    {
        return $this->fetch();
    }

    private function helper()
    {
        return 'helper';
    }
}

?>
-----
<?php

namespace app\controller;

use think\Controller;

class Product extends Controller
{
    public function list(): string
    {
        return $this->fetch();
    }

    public function detail($id): string
    {
        return $this->fetch();
    }

    public function search(): string
    {
        return $this->fetch();
    }

    private function helper()
    {
        return 'helper';
    }
}

?>
