<?php

class IndexController
{
    public function hello()
    {
        echo 'hello,thinkphp!';
    }
    
    public function welcome()
    {
        echo 'Welcome to ThinkPHP 5.0';
    }
}

?>
-----
<?php

class IndexController
{
    public function hello()
    {
        return 'hello,thinkphp!';
    }
    
    public function welcome()
    {
        return 'Welcome to ThinkPHP 5.0';
    }
}

?>
