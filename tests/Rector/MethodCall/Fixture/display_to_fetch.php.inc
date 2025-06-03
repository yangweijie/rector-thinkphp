<?php

class IndexController extends Controller
{
    public function index()
    {
        $this->display();
    }
    
    public function list()
    {
        $data = $this->getData();
        $this->assign('data', $data);
        $this->display();
    }
}

?>
-----
<?php

class IndexController extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
    
    public function list()
    {
        $data = $this->getData();
        $this->assign('data', $data);
        return $this->fetch();
    }
}

?>
