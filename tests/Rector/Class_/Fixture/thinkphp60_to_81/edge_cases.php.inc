<?php

namespace app\controller;

use think\Controller;

class EdgeCaseController extends Controller
{
    // Property already readonly
    private readonly $readonlyProp;
    
    // Property without constructor assignment
    private $standaloneProperty;
    
    // Constructor with no parameters
    public function __construct()
    {
        $this->standaloneProperty = 'value';
    }
    
    public function action(): string
    {
        return $this->fetch();
    }
}

class NoConstructorController extends Controller
{
    private $property = 'default';
    
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

class EdgeCaseController extends Controller
{
    // Property already readonly
    private readonly $readonlyProp;
    
    // Property without constructor assignment
    private $standaloneProperty;
    
    // Constructor with no parameters
    public function __construct()
    {
        $this->standaloneProperty = 'value';
    }
    
    public function action(): string
    {
        return $this->fetch();
    }
}

class NoConstructorController extends Controller
{
    private $property = 'default';
    
    public function index(): string
    {
        return $this->fetch();
    }
}

?>
