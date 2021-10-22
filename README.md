arab-cover-text
===============

> 针对阿拉伯字符进行顺序修正

~~~php
<?php
$cover = new \BusyPHP\helper\ArabCoverHelper();
$string = $cover->convert('ئالما نۇسقىسنى چۈشرۈش');

var_dump($string);
~~~