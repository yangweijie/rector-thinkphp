<?php

class ProductModel extends Model
{
    protected $tableName = 'products';
    protected $pk = 'product_id';
    
    public function getActiveProducts()
    {
        return $this->where('status', 1)->select();
    }
}

?>
-----
<?php

namespace app\index\model;

use think\Model;
class Product extends Model
{
    protected $table = 'products';
    protected $pk = 'product_id';
    
    public function getActiveProducts()
    {
        return $this->where('status', 1)->select();
    }
}

?>
