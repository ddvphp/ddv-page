ddv-page 
===================

Installation - 安装
------------

```bash
composer require ddvphp/ddv-page
```

Usage - 使用
-----

## 1、laravel framework Usage[laravel 框架 使用]


## 2、CodeIgniter framework Usage[CodeIgniter 框架 使用]


```php
$page = new DdvPhp/DdvPage();
$pc = array();
//传入当前的页数
$pc['now'] = $page_now;
//传入数据库总数据条数
$pc['count'] = $r['count'];
//每页多少条
$pc['size'] = $page_size;
//初始化分页配置
$pr = $page->init($pc);
//获取分页结果数据
$limit = $page->getLimit();
//获取limit的参数
$sql = '.......limit $limit[0],$limit[1] ....... ;';

//获取分页结果数据
$pr = $page->getPage();
$sql = '.......limit $pr["limitStart"],$pr["size"] ....... ;';
$pr格式
array(
	//当前页数
	'now'=>1,
	//输入的页数
	'input_page'=>1,
	//数据库数据总条数
	'count'=>0,
	//每页显示条数
	'size'=>10,
	//最后一页是第几页
	'end'=>10,
	//上一页页数是
	'before'=>1,
	//下一页页数是
	'after'=>1,
	//默认页数列条数
	'lists_size'=>10,
	//页数列表
	'lists'=>array(),
	//是否为传入的页数
	'is_input_page'=>true,
	//是否到达尾页
	'is_end'=>false,
	//limit开始位置
	'limit_start'=>0
)
```