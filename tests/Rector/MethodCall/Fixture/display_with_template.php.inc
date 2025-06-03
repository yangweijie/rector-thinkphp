<?php

class UserController extends Controller
{
    public function profile()
    {
        $this->display('profile');
    }
    
    public function settings()
    {
        $this->display('user_settings');
    }
}

?>
-----
<?php

class UserController extends Controller
{
    public function profile()
    {
        return $this->fetch('profile');
    }
    
    public function settings()
    {
        return $this->fetch('user_settings');
    }
}

?>
