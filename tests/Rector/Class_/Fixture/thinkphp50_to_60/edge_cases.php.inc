<?php

namespace app\index\controller;

use think\Controller;

class EdgeCase extends Controller
{
    // Method already has return type
    public function alreadyTyped(): string
    {
        return $this->fetch();
    }

    // Method with complex namespace
    public function complexMethod()
    {
        return $this->fetch();
    }

    // Constructor should not get return type
    public function __construct()
    {
        parent::__construct();
    }

    // Magic methods should not get return type
    public function __toString()
    {
        return 'string';
    }
}

?>
-----
<?php

namespace app\controller;

use think\Controller;

class EdgeCase extends Controller
{
    // Method already has return type
    public function alreadyTyped(): string
    {
        return $this->fetch();
    }

    // Method with complex namespace
    public function complexMethod(): string
    {
        return $this->fetch();
    }

    // Constructor should not get return type
    public function __construct()
    {
        parent::__construct();
    }

    // Magic methods should not get return type
    public function __toString()
    {
        return 'string';
    }
}

?>
