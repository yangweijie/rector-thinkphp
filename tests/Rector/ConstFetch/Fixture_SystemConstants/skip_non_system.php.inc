<?php

class TestController
{
    public function index()
    {
        $pi = M_PI;
        $max = PHP_INT_MAX;
        $custom = CUSTOM_CONSTANT;
        
        // These should not be changed
        return 'success';
    }
}

?>
-----
<?php

class TestController
{
    public function index()
    {
        $pi = M_PI;
        $max = PHP_INT_MAX;
        $custom = CUSTOM_CONSTANT;
        
        // These should not be changed
        return 'success';
    }
}

?>
