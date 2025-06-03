<?php

namespace app\index\model;

use think\Model;

class User extends Model
{
    protected $table = 'users';
    
    public function getProfile()
    {
        return $this->hasOne('Profile');
    }

    public function posts()
    {
        return $this->hasMany('Post');
    }
}

?>
-----
<?php

namespace app\model;

use think\Model;

class User extends Model
{
    protected $table = 'users';
    
    public function getProfile()
    {
        return $this->hasOne('Profile');
    }

    public function posts()
    {
        return $this->hasMany('Post');
    }
}

?>
