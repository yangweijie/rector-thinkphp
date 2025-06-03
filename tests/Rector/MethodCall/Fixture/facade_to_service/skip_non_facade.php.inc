<?php

namespace app\controller;

use think\Controller;

class UtilController extends Controller
{
    public function test()
    {
        $result = SomeClass::staticMethod();
        $data = MyHelper::process($result);
        return json(['data' => $data]);
    }
}

?>
