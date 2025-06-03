<?php

class IndexController
{
    public function test()
    {
        print('Test message');
    }
}

?>
-----
<?php

class IndexController
{
    public function test()
    {
        return 'Test message';
    }
}

?>
