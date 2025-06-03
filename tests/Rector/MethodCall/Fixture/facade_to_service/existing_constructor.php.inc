<?php

namespace app\controller;

use think\Controller;
use think\facade\Db;

class OrderController extends Controller
{
    private $orderService;

    public function __construct($orderService)
    {
        $this->orderService = $orderService;
    }

    public function list()
    {
        $orders = Db::table('orders')->select();
        return $this->fetch('list', ['orders' => $orders]);
    }
}

?>
-----
<?php

namespace app\controller;

use think\Controller;
use think\facade\Db;

class OrderController extends Controller
{
    private $orderService;

    public function __construct($orderService)
    {
        $this->orderService = $orderService;
    }

    public function list()
    {
        $orders = $this->db->table('orders')->select();
        return $this->fetch('list', ['orders' => $orders]);
    }
}

?>
