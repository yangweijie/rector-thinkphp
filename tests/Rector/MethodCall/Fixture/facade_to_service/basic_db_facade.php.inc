<?php

namespace app\controller;

use think\Controller;
use think\facade\Db;

class UserController extends Controller
{
    public function index()
    {
        $users = Db::table('users')->select();
        return $this->fetch();
    }

    public function create()
    {
        $result = Db::table('users')->insert(['name' => 'test']);
        return json(['success' => $result]);
    }
}

?>
-----
<?php

namespace app\controller;

use think\Controller;
use think\facade\Db;

class UserController extends Controller
{
    public function index()
    {
        $users = $this->db->table('users')->select();
        return $this->fetch();
    }

    public function create()
    {
        $result = $this->db->table('users')->insert(['name' => 'test']);
        return json(['success' => $result]);
    }
}

?>
