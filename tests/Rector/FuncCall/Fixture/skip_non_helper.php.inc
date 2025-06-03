<?php

class TestController
{
    public function index()
    {
        $result = strlen('test');
        $array = array_merge([1, 2], [3, 4]);
        $custom = customFunction();
        
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
        $result = strlen('test');
        $array = array_merge([1, 2], [3, 4]);
        $custom = customFunction();
        
        // These should not be changed
        return 'success';
    }
}

?>
