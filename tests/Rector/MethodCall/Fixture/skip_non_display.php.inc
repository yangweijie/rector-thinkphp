<?php

class SomeController extends Controller
{
    public function index()
    {
        $this->assign('data', []);
        $this->redirect('/home');
    }
    
    public function ajax()
    {
        $this->ajaxReturn(['status' => 'success']);
    }
    
    public function show()
    {
        $this->show('template');
    }
}

?>
-----
<?php

class SomeController extends Controller
{
    public function index()
    {
        $this->assign('data', []);
        $this->redirect('/home');
    }
    
    public function ajax()
    {
        $this->ajaxReturn(['status' => 'success']);
    }
    
    public function show()
    {
        $this->show('template');
    }
}

?>
