<?php

namespace app\controller;

use think\Controller;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

class ProductController extends Controller
{
    public function show($id)
    {
        $cacheKey = 'product_' . $id;
        $product = Cache::get($cacheKey);
        
        if (!$product) {
            $product = Db::table('products')->find($id);
            Cache::set($cacheKey, $product, 3600);
            Log::info('Product loaded from database', ['id' => $id]);
        }
        
        return $this->fetch('show', ['product' => $product]);
    }
}

?>
-----
<?php

namespace app\controller;

use think\Controller;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Log;

class ProductController extends Controller
{
    public function show($id)
    {
        $cacheKey = 'product_' . $id;
        $product = $this->cache->get($cacheKey);
        
        if (!$product) {
            $product = $this->db->table('products')->find($id);
            $this->cache->set($cacheKey, $product, 3600);
            $this->log->info('Product loaded from database', ['id' => $id]);
        }
        
        return $this->fetch('show', ['product' => $product]);
    }
}

?>
