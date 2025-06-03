<?php

namespace app\controller;

use think\Controller;

abstract class BaseController extends Controller
{
    protected $logger;
    
    public function __construct($logger)
    {
        $this->logger = $logger;
    }
}

class AdminController extends BaseController
{
    private $adminService;
    
    public function __construct($logger, $adminService)
    {
        parent::__construct($logger);
        $this->adminService = $adminService;
    }
    
    public function dashboard(): string
    {
        return $this->fetch();
    }
}

?>
-----
<?php

namespace app\controller;

use think\Controller;

abstract class BaseController extends Controller
{
    public function __construct(protected $logger)
    {
    }
}

class AdminController extends BaseController
{
    private $adminService;
    
    public function __construct($logger, $adminService)
    {
        parent::__construct($logger);
        $this->adminService = $adminService;
    }
    
    public function dashboard(): string
    {
        return $this->fetch();
    }
}

?>
