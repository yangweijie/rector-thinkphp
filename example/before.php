<?php

namespace app\index\controller;

use think\Controller;
// ThinkPHP 3.2 代码示例
class User extends Controller
{
    public function index()
    {
        return $this->fetch();
    }
    
    public function profile()
    {
        $user = $this->getUser();
        $this->assign('user', $user);
        return $this->fetch('User/profile');
    }
    
    public function list()
    {
        $users = D('User')->getUserList();
        $this->assign('users', $users);
        return $this->fetch('User/list');
    }
}

namespace app\index\model;

use think\Model;
class User extends Model
{
    protected $table = 'users';
    protected $pk = 'user_id';
    
    public function getUserList()
    {
        return $this->where('status', 1)->select();
    }
    
    public function getUserById($id)
    {
        return $this->where('user_id', $id)->find();
    }
}

namespace app\index\model;

use think\Model;
class Product extends Model
{
    protected $table = 'products';
    
    public function getActiveProducts()
    {
        return $this->where('status', 'active')->select();
    }
}
