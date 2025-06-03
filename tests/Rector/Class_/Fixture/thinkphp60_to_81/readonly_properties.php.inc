<?php

namespace app\model;

use think\Model;

class Product extends Model
{
    private $config;
    private $immutableData;
    
    public function __construct($config, $immutableData)
    {
        $this->config = $config;
        $this->immutableData = $immutableData;
    }
    
    public function getConfig()
    {
        return $this->config;
    }
}

?>
-----
<?php

namespace app\model;

use think\Model;

class Product extends Model
{
    public function __construct(private readonly $config, private readonly $immutableData)
    {
    }
    
    public function getConfig()
    {
        return $this->config;
    }
}

?>
