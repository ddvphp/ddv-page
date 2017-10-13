<?php
namespace DdvPhp;
// 字符串转换库
use \DdvPhp\DdvUtil\String\Conversion;

/**
 * @mixin \DdvPhp\DdvPageStatic
 */
class DdvPage {
  protected $flagPageLists = true;
  protected static $listsArrayDefault = array();
  protected $lists = null;
  protected $pageArray = array();
  protected static $pageColumns = array('now', 'count', 'size', 'end', 'isEnd', 'isInputPage');
  /**
   * 实例化一个DdvPage对象
   * @param \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder|false $obj   [数据库对象模型]
   * @return \DdvPhp\DdvPage $page [分页对象]
   */
  public static function create($obj = false){
    $className =  get_called_class();
    $page = new $className(false);
    call_user_func_array(array($page, '__construct'), func_get_args());
    return $page;
  }
  /**
   * 构造函数
   * @param \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder|false $obj   [数据库对象模型]
   * @return \DdvPhp\DdvPage $page [分页对象]
   */
  public function __construct($obj)
  {
    $this->lists = self::$listsArrayDefault;
    if($obj===false){
      return $this;
    }
    // 初始化
    is_array($obj) ? call_user_func_array(array($this, 'init'), func_get_args()) : $this->init(array());
    // 如果是一个对象
    if(is_object($obj)){
      call_user_func_array(array($this, 'initByObj'), func_get_args());
    }
    return $this;
  }
  /**
   * 通过对象来初始化
   * @param \Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder $obj   [数据库对象模型]
   * @return \DdvPhp\DdvPage $page [分页对象]
   */
  public function initByObj($obj)
  {
    if (!is_object($obj)){
      return $this;
    }elseif (class_exists('Illuminate\Pagination\LengthAwarePaginator') && $obj instanceof \Illuminate\Pagination\LengthAwarePaginator){
      // 如果是 分页对象
      call_user_func_array(array($this, 'LengthAwarePaginatorInit'), func_get_args());
    }elseif (class_exists('Illuminate\Database\Eloquent\Builder') && $obj instanceof \Illuminate\Database\Eloquent\Builder){
       // 如果是 数据库模型对象
        call_user_func_array(array($this, 'DatabaseBuilderInit'), func_get_args());
    }
    return $this;
  }
  /**
   * 设置保存数据 驼峰自动转小写下划线
   * @param \Illuminate\Database\Eloquent\Builder $obj   [数据库对象模型]
   * @param int|boolean|null $pageSize [分页每页大小]
   * @param array  $columns [筛选字段]
   * @param int|null  $pageNow [读取第几页]
   * @param array $data [需要保存的数组]
   * @return \DdvPhp\DdvPage $page [分页对象]
   */
  public function DatabaseBuilderInit($obj, $pageSize = null, $columns = ['*'], $pageNow = null)
  {
    if ($pageSize===false) {
      $this->pageArray = null;
      $this->lists = $obj->get($columns);
    }else{
      $pageNow = empty($pageNow) ? $this->pageArray['inputPage'] : intval($pageNow);
      $pageSize = empty($pageSize) || $pageSize === true ? $this->pageArray['size'] : intval($pageSize);
      $this->LengthAwarePaginatorInit($obj->paginate($pageSize, $columns, 'pageNow', $pageNow));
    }
    return $this;
  }
  /**
   * 设置保存数据 驼峰自动转小写下划线
   * @param \Illuminate\Pagination\LengthAwarePaginator $obj [分页对象]
   * @return \DdvPhp\DdvPage $page [分页对象]
   */
  public function LengthAwarePaginatorInit($obj)
  {
    $this->pageArray['now'] = intval($obj->currentPage());
    $this->pageArray['count'] = intval($obj->total());
    $this->pageArray['size'] = intval($obj->perPage());
    $this->pageArray['end'] = intval($obj->lastPage());
    $this->setup();
    $lists = array();
    foreach ($obj->items() as $index => $item) {
      $lists[$index] = $item;
    }
    $this->lists = empty($lists) ? $this->lists : $lists;
    return $this;
  }
  /**
  * 初始化
  * @param array  $params [配置]
  * @return \DdvPhp\DdvPage $page [分页对象]
  */
  public function init($params = array(),$flag=null)
  {
    $this->pageArray = array_merge($this->pageArray, array(
      //当前页数
      'now'=>null,
      //数据库数据总条数
      'count'=>0,
      //每页显示条数
      'size'=>null,
      //最后一页是第几页
      'end'=>10,
      //上一页页数是
      'before'=>1,
      //下一页页数是
      'after'=>1,
      //默认页数列条数
      'listsSize'=>10,
      //页数列表
      'lists'=>array(),
      //输入的页数
      'inputPage'=>null,
      //是否到达尾页
      'isEnd'=>false,
      //是否为传入的页数
      'isInputPage'=>true,
      //limit开始位置
      'limitStart'=>0
    ), $params);
    $pageNow = null;
    $pageSize = null;
    $dataInput = array($_GET, $_POST, $_REQUEST);
    foreach ($dataInput as $index => $data) {
      if (empty($pageNow)) {
        $pageNow = empty($data['page_now']) ? $pageNow : $data['page_now'];
        $pageNow = empty($data['pageNow']) ? $pageNow : $data['pageNow'];
      }
      if (empty($pageSize)) {
        $pageSize = empty($data['page_size']) ? $pageSize : $data['page_size'];
        $pageSize = empty($data['pageSize']) ? $pageSize : $data['pageSize'];
      }
    }
    if (empty($this->pageArray['now']) && empty($this->pageArray['inputPage'])) {
      $this->pageArray['inputPage'] = $this->pageArray['now'] = empty($pageNow) ? 1 : $pageNow ;
    }
    if (empty($this->pageArray['size'])) {
      $this->pageArray['size'] = empty($pageSize) ? 10 : $pageSize ;
    }
    $this->setup($flag);
    return $this;
  }
  /**
   * 获取sql的偏移和长度[limit]
   * @return array $limit [分页对象]
   */
  public function getLimit(){
    $r = array($this->pageArray['limitStart'], $this->pageArray['size']);
    return $r ;
  }
  /**
   * 获取分页数据[limit]
   * @param array|null  $pageColumns [分页数字字段]
   * @return array $limit [分页字段]
   */
  public function getPage($pageColumns = null){
    $pageColumns = empty($pageColumns) && (!is_array($pageColumns)) ? self::$pageColumns : $pageColumns;
    if (empty($pageColumns)) {
      return $this->pageArray;
    }else{
      $r = array();
      foreach ($pageColumns as $index => $key) {
        $r[$key] = $this->pageArray[$key];
      }
      return $r;
    }
  }
  /**
   * 设定分页字段数组[limit]
   * @param array|null  $pageColumns [分页数字字段]
   * @return \DdvPhp\DdvPage $page [分页对象]
   */
  public static function setPageColumnsByStatic($pageColumns = null){
    self::$pageColumns = empty($pageColumns) ? array() : $pageColumns;
  }
  public static function setListsArrayDefaultStatic($listsArrayDefault)
  {
    self::$listsArrayDefault = $listsArrayDefault;
  }
  public function setup($flag=null){
    $this->flagPageLists = is_null($flag) ? $this->flagPageLists : $flag;
    $c = &$this->pageArray;
    $c['now'] = intval($c['now']);
    $c['inputPage'] = $c['now'];
    $c['count'] = intval($c['count']);
    $c['size'] = intval($c['size']);
    if ($c['now']<1) {
      $c['now'] = 1 ;
    }
    if ($c['count']<1) {
      $c['count'] = 0 ;
    }
    if ($c['size']<1) {
      $c['size'] = 10 ;
    }
    $c['end'] = intval(ceil($c['count']/$c['size']));
    if ($c['now']>$c['end']) {
      $c['now'] = $c['end'] ;
    }
    $c['limitStart'] = ( abs(( $c['now'] - 1 )) * $c['size'] ) ;

    //重新计算上一页
    $c['before'] = $c['now'] - 1 ;
    $c['before'] = ( $c['before'] < 1 ) ? $c['now'] : $c['before'] ;
    //重新计算下一页
    $c['after'] = $c['now'] + 1 ;
    $c['after'] = ( $c['after'] > $c['end'] ) ? $c['end'] : $c['after'] ;
    $c['lists'] = array();
    //统计当前页前
    $i = intval($c['now']) ;
    $ilen = $i-intval($c['listsSize']/2) ;
    for ($i = intval($i) ; $i >= $ilen ; $i--) {
      if ($i>=1) {
        array_unshift($c['lists'],$i);
      };
    };
    //补充当前页后
    $i = intval($c['now']) + 1 ;
    $ilen = $i + intval(intval($c['listsSize'])-intval(count($c['lists'])));
    for ($i = intval($i); $i < $ilen; $i++) {
      if ($i<=$c['end']) {
        $c['lists'][] = $i;
      };
    };
    //向前边补充差额
    if (isset($c['lists'][0])){
      $i = $c['lists'][0] ;
      $ilen = $i - intval(intval($c['listsSize'])-intval(count($c['lists'])));
      for ($i = intval($i) ; $i > $ilen ; $i--) {
        if ($i>=1) {
          array_unshift($c['lists'],$i);
        };
      };
    };
    if(!$this->flagPageLists){
      unset($c['lists']);
    }else{
      $c['lists'] = array_combine(array_merge(array_unique($c['lists'])),array_merge(array_unique($c['lists'])));
    }


    //判断是否到了尾页
    $c['isEnd'] = (bool)($c['now'] == $c['end']) ;
    //判断是否为传入输入的页数
    $c['isInputPage'] = (bool)($c['inputPage'] == $c['now']) ;

    $c['limit_start'] = &$c['limitStart'];
    $c['lists_size'] = &$c['listsSize'];
    $c['input_page'] = &$c['inputPage'];
    $c['is_end'] = &$c['isEnd'];
    $c['is_input_page'] = &$c['isInputPage'];
  }
  /**
   * 获取分页数据和数据库数据[自动转驼峰]
   * @param array|null  $pageColumns [分页数字字段]
   * @return array $res [分页数据和查询数据]
   */
  public function toHumpArray($pageColumns = null){
      $res = $this->toArray();
      if (!empty($res['lists'])) {
          $res['lists'] = Conversion::underlineToHumpByIndexArray($res['lists']);
      }
      if (!empty($res['page'])) {
          $res['page'] = Conversion::underlineToHumpByIndexArray($res['page']);
      }
      return $res;
  }
  /**
   * 获取分页数据和数据库数据
   * @param array|null  $pageColumns [分页数字字段]
   * @return array $res [分页数据和查询数据]
   */
  public function getRes($pageColumns = null){
      $page = array();
      $pageColumns = empty($pageColumns) && (!is_array($pageColumns)) ? self::$pageColumns : $pageColumns;
      if (empty($pageColumns)) {
          $page = $this->pageArray;
      }else{
          $page = array();
          foreach ($pageColumns as $index => $key) {
              $page[$key] = $this->pageArray[$key];
          }
      }
      $lists = empty($this->lists)?array():$this->lists;
      return array(
          'lists'=>$lists,
          'page'=>$page
      );
  }
  /**
   * 获取分页数据和数据库数据
   * @param array|null  $pageColumns [分页数字字段]
   * @return array $res [分页数据和查询数据]
   */
  public function toArray($pageColumns = null){
      $res = $this->getRes();

      $lists = &$res['lists'];
      if (is_object($lists)&&method_exists($lists, 'toArray')){
          $lists = $lists->toArray();
      } elseif (is_array($lists)){
          foreach ($lists as $index => $value){
              if (is_object($value)&&method_exists($value, 'toArray')){
                  $lists[$index] = $value->toArray();
              }
          }
      }
      return $res;
  }
  /**
   * Convert the object into something JSON serializable.
   *
   * @return array
   */
  public function jsonSerialize()
  {
    return $this->toArray();
  }

  /**
   * Convert the object to its JSON representation.
   *
   * @param  int  $options
   * @return string
   */
  public function toJson($options = 0)
  {
    return json_encode($this->jsonSerialize(), $options);
  }
  /**
   * Convert the object to its JSON representation.
   *
   * @param  int  $options
   * @return string
   */
  public function __toString()
  {
    return $this->toJson();
  }
  /**
   * Handle dynamic method calls into the model.
   *
   * @param  string  $method
   * @param  array  $parameters
   * @return mixed
   */
  public function __call($method, $parameters)
  {
    if ($method === 'setListsArrayDefault') {
      self::setListsArrayDefaultStatic(...$parameters);
    } elseif ($method === 'setPageColumns') {
      self::setPageColumnsByStatic(...$parameters);
    }else{
        throw new \DdvPhp\DdvUtil\Exception('Not Method', 'NOT_METHOD');
    }
    return $this;
  }
  /**
   * Handle dynamic static method calls into the method.
   *
   * @param  string  $method
   * @param  array  $parameters
   * @return mixed
   */
  public static function __callStatic($method, $parameters)
  {
    if ($method === 'setListsArrayDefault') {
      self::setListsArrayDefaultStatic(...$parameters);
    } elseif ($method === 'setPageColumns') {
      self::setPageColumnsByStatic(...$parameters);
    }else{
      throw new \DdvPhp\DdvUtil\Exception('Not Method', 'NOT_METHOD');
    }

  }
}
class DdvPageStatic{

    public static function setListsArrayDefault($listsArrayDefault)
    {
        self::setListsArrayDefaultStatic($listsArrayDefault);
    }
    /**
     * 设定分页字段数组[limit]
     * @param array|null  $pageColumns [分页数字字段]
     * @return \DdvPhp\DdvPage $page [分页对象]
     */
    public static function setPageColumns($pageColumns = null){
        self::setPageColumnsByStatic($pageColumns);
    }
}