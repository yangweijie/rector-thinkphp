<?php

class UserModel extends Model
{
    public function getUserList()
    {
        return $this->select();
    }
    
    public function getUserById($id)
    {
        return $this->where('id', $id)->find();
    }
}

?>
-----
<?php

namespace app\index\model;

use think\Model;
class User extends Model
{
    public function getUserList()
    {
        return $this->select();
    }
    
    public function getUserById($id)
    {
        return $this->where('id', $id)->find();
    }
}

?>
