<?php

class RequestController
{
    public function index()
    {
        if (REQUEST_METHOD === 'POST') {
            // handle POST request
        }

        if (IS_AJAX) {
            // handle AJAX request
        }

        $module = MODULE_NAME;
        $controller = CONTROLLER_NAME;
        $action = ACTION_NAME;
        
        $isGet = IS_GET;
        $isPost = IS_POST;
    }
}

?>
-----
<?php

class RequestController
{
    public function index()
    {
        if (request()->method() === 'POST') {
            // handle POST request
        }

        if (request()->isAjax()) {
            // handle AJAX request
        }

        $module = request()->module();
        $controller = request()->controller();
        $action = request()->action();
        
        $isGet = request()->isGet();
        $isPost = request()->isPost();
    }
}

?>
