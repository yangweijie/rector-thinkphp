<?php

namespace app\controller;

use think\Controller;
use app\service\UserService;

class User extends Controller
{
    private $userService;
    private $cacheService;
    
    public function __construct(UserService $userService, $cacheService)
    {
        $this->userService = $userService;
        $this->cacheService = $cacheService;
    }
    
    public function index(): string
    {
        return $this->fetch();
    }
}

?>
-----
<?php

namespace app\controller;

use think\Controller;
use app\service\UserService;

class User extends Controller
{
    public function __construct(private UserService $userService, private $cacheService)
    {
    }
    
    public function index(): string
    {
        return $this->fetch();
    }
}

?>
