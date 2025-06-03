<?php

class AdminController extends Controller
{
    public function userList()
    {
        $this->display('User:list');
    }
    
    public function userEdit()
    {
        $this->display('User:edit');
    }
    
    public function dashboard()
    {
        $this->display('Admin:dashboard');
    }
}

?>
-----
<?php

class AdminController extends Controller
{
    public function userList()
    {
        return $this->fetch('User/list');
    }
    
    public function userEdit()
    {
        return $this->fetch('User/edit');
    }
    
    public function dashboard()
    {
        return $this->fetch('Admin/dashboard');
    }
}

?>
