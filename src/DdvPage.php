<?php

namespace DdvPhp;
use const null;


class DdvPage {
  public $listsArray = null;
  public $pageArray = array();
  public function __construct($obj)
  {
    if(is_array($obj)){
      $this->init($obj);
    }elseif(is_object($obj)){
      call_user_func_array(array($this, 'initByObj'), func_get_args());
    }
  }
  public function initByObj($obj)
  {
    $className = get_class($obj);
    switch ($className) {
      case 'Illuminate\Pagination\LengthAwarePaginator':
        if (class_exists('Illuminate\Pagination\LengthAwarePaginator')) {
          call_user_func_array(array($this, 'LengthAwarePaginatorInit'), func_get_args());
        }
        break;
      case 'Illuminate\Database\Eloquent\Builder':
        if (class_exists('Illuminate\Database\Eloquent\Builder')) {
          call_user_func_array(array($this, 'DatabaseBuilderInit'), func_get_args());
        }
        break;
      
      default:
        break;
    }
  }
  public function DatabaseBuilderInit($obj, $pageNow = 1, $pageSize = 10, $columns = ['*'])
  {
    $this->LengthAwarePaginatorInit($obj->paginate($pageSize, $columns, 'pageNow', $pageNow));
  }
  public function LengthAwarePaginatorInit($obj)
  {
    $this->pageArray = array_merge($this->pageArray, array(
      //当前页数
      'now'=>$obj->currentPage(),
      //输入的页数
      'input_page'=>$obj->currentPage(),
      //数据库数据总条数
      'count'=>$obj->total(),
      //每页显示条数
      'size'=>$obj->perPage(),
      //最后一页是第几页
      'end'=>$obj->lastPage(),
      //上一页页数是
      'before'=>$obj->currentPage(),
      //下一页页数是
      'after'=>$obj->currentPage() + 1,
      //页数列表
      'lists'=>array(),
      //是否为传入的页数
      'is_input_page'=>true,
      //是否到达尾页
      'is_end'=>false
    ));
    if ($this->pageArray['before']<1) {
      $this->pageArray['before'] = 1;
    }
    if ($this->pageArray['after']>$this->pageArray['end']) {
      $this->pageArray['after'] = $this->pageArray['end'];
    }
    $this->pageArray['is_end'] = $this->pageArray['after'] === $this->pageArray['end'];
    $this->init($this->pageArray);
    $this->__setup();
    $lists = array();
    $items = $obj->items();
    $itemsLen = count($items);
    for($i=0; $i<$itemsLen;$i++){
      $lists[$i] = $items[$i]->toArray();
    }
    $this->listsArray = empty($lists)?$this->pageArray:$lists;
    return;
  }
  public function init($params = array(),$flag=true)
  {
    (isset($this->pageArray)&&is_array($this->pageArray)) || ($this->pageArray = array());
    (isset($c)&&is_array($c)) || ($c = array());
    $this->C(array(
      //当前页数
      'now'=>1,
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
      'listsSize'=>10,
      //页数列表
      'lists'=>array(),
      //输入的页数
      'input_page'=>1,
      //是否到达尾页
      'is_end'=>false,
      //是否为传入的页数
      'is_input_page'=>true,
      //limit开始位置
      'limitStart'=>0
    ));
    $this->C($params);
    $this->__setup($flag);
  
    return $this;
  }
  private function __setup($flag=true){

    $c = $this->C();
    $c['now'] = intval($c['now']);
    $c['input_page'] = $c['now'];
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
    if(!$flag){
      $c['lists']=array();
    }else{
      $c['lists'] = array_combine(array_merge(array_unique($c['lists'])),array_merge(array_unique($c['lists'])));
    }
    



    //判断是否到了尾页
    $c['is_end'] = (bool)($c['now'] == $c['end']) ;
    //判断是否为传入输入的页数
    $c['is_input_page'] = (bool)($c['input_page'] == $c['now']) ;
    $this->C($c);
  }
  public function getLimit(){
    $c = &$this->C();
    $r = array($c['limitStart'],$c['size']);
    return $r ;
  }
  public function getPage(){
    return $this->C();  
  }


  /*
  C('name','value');储存
  C('name.name1.name2','value');储存
  C('name.name1.name2');读取
  C(array('a'=>2));存储
  */
  private function &C(){
    $r = NULL ;
    $c = &$this->pageArray ;
    $c_tmp_1 = '';
    $c_tmp_2 = '';
    $i = 0 ;
    $c_names = array();
    $c_names_len = 0 ;
    $num = func_num_args() ;
    $args = func_get_args() ;
    if($num==0){
      $r = $c ;
    }elseif ($num==1&&isset($args[0])&&is_array($args[0])) {
      $c = array_merge($c,$args[0]) ;
      $r = TRUE ;
    }elseif (($num==1||$num==2)&&isset($args[0])&&is_string($args[0])) {
      $c_names = explode('.',$args[0]);
      $c_names_len = count($c_names) ;
      $c_tmp_1 = &$c ;
      for ($i=0; $i < $c_names_len ; $i++) {
        if(!empty($c_names[$i])){
          $c_tmp_2='';
          unset( $c_tmp_2 ) ;
          if (isset($c_tmp_1[$c_names[$i]])) {
            $c_tmp_2 = &$c_tmp_1[$c_names[$i]] ;
            unset($c_tmp_1) ;
            $c_tmp_1 = &$c_tmp_2 ;
            $r =  $c_tmp_1;
          }elseif ($num==2) {
            $c_tmp_1[$c_names[$i]] = array() ;
            $c_tmp_2 = &$c_tmp_1[$c_names[$i]] ;
            unset($c_tmp_1) ;
            $c_tmp_1 = &$c_tmp_2 ;
          }else{
            $r = NULL ;
          }
          unset( $c_tmp_2 );
          $c_tmp_2='';
        }
      }
      if ($num==2) {
        $c_tmp_1 = $args[1] ;
      }
    }
    unset($c) ;unset($num) ;unset($args) ;
    return $r ;
  }
  public function toArray(){
    return array(
      'lists'=>$this->listsArray,
      'page'=>$this->pageArray
    );
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
  public function __toString()
  {
    return json_encode($this->toArray());
  }
}
