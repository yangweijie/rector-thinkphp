<?php

use think\facade\Route;

Route::rule([
    'hello/:name' => 'index/hello',
    'user/:id' => 'user/read'
]);

Route::rule('single/:id', 'single/read');

?>
-----
<?php

use think\facade\Route;

Route::rules([
    'hello/:name' => 'index/hello',
    'user/:id' => 'user/read'
]);

Route::rule('single/:id', 'single/read');

?>
