<?php

namespace app\service;

class UserService
{
    public function getUserList()
    {
        return [];
    }

    public function createUser($data)
    {
        return true;
    }
}

class CustomClass extends \stdClass
{
    public function method()
    {
        return 'unchanged';
    }
}

?>
