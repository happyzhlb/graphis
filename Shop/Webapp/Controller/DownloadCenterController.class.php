<?php
/**
 * Webapp下载控制器
 * @author Abiao
 * @copyright 2014
 */
namespace Webapp\Controller;
use Think\Page;

use Think\Controller;
class DownloadCenterController extends FrontendController{ 
	var $_goods_mod=null; 
    function __construct(){
        parent::__construct();
        $this->DownloadCenterController();
    }
    
    function DownloadCenterController(){
        $this->_goods_mod=D('goods');   
        $this->_brand_mod=D('brand'); 
        $this->_coa_mod=D('goods_coa'); 
        $this->_config_seo(array('name'=>'Download Center(MSDS,COA)'));
    }
    
    //MSDS列表 
    function index(){    
    		$brands=$this->_brand_mod->where('if_show=1')->select();
       		foreach ($brands as $key => $val){
          		$brands[$key]['goods']=D('Brands')->getMsdsByBrand($val['bid']);
       		}
       		$this->assign('brands',$brands);
       		$this->display('./Technology_Center');       
    }    
    //MSDS搜索
    function MSDS(){ 
    	$goods_name=I('goods_name','','trim');
    		$where='b.if_show=1'; 
    		if($goods_name!=''){
    			$where.=' and g.goods_name like "%'.$goods_name.'%"';
    		}
    		$totalRows=$this->_brand_mod->join(' as b join __GOODS__ as g on b.bid=g.bid')->where($where) 
      ->count(); 
      		$page=New Page($totalRows,10,array('goods_name'=>$goods_name));
    		$list=$this->_brand_mod->field('g.goods_id,g.goods_name,g.goods_code,g.msds,b.bid,b.bname')
      ->join(' as b join __GOODS__ as g on b.bid=g.bid')->where($where)
      ->order('g.bid,b.sort_order desc,g.goods_id desc')->limit($page->firstRow,$page->listRows)
      ->select();
       		$this->assign('totalRows',$totalRows);
       	    $this->assign('page',$page->show());
      		$this->assign('goods_name',$goods_name);
      		$this->assign('brands',$list);
      		$this->display('./Technology_Center');
    }
    
    //COA列表+搜索
	function COA(){ 
	  $goods_name=I('goods_name','','trim');
	  $batch=I('batch','','trim');
	  if($goods_name!=''){
	  	$where['g.goods_name']=array('like',"%$goods_name%");
	  }
	  if($batch!=''){
	  	$where['c.batch']=array('like',"%$batch%"); 
	  } 
	  $totalRows=$this->_goods_mod
	  ->join(' as g join __GOODS_COA__ as c on c.goods_id=g.goods_id')->where($where) 
      ->count();  
      $page=new Page($totalRows, 15);
      $list=$this->_goods_mod->field('g.goods_id,g.goods_name,g.goods_code,g.msds,c.cid,c.batch,c.file')
      ->join(' as g join __GOODS_COA__ as c on c.goods_id=g.goods_id')->where($where)
      ->order('g.goods_id desc')->limit($page->firstRow,$page->listRows)
      ->select();   
      $this->assign('totalRows',$totalRows);
      $this->assign('page',$page->show());
      $this->assign('list',$list);
	  $this->display('./Technology_Center_COA');
	}
    
   function coa_pdf(){  
   	$cid=I('cid','','trim');
   	if(empty($cid)){
   		exit('Error Param.');
   	}
   	$file=$this->_coa_mod->getFieldByCid($cid,'file');
   	$file='/'.$file; 
   	 echo '<script type="text/javascript" src="/Public/home/js/pdfobject.js"></script>';
   	 echo '<script> 
   	 		window.onload = function (){ 
           var success = new PDFObject({ url: "'.$file.'" }).embed();
      };</script>';
   	 echo  "<body>
    <p style='margin:100px 0 0 00;text-align:center; font-family:Arial, Helvetica, sans-serif;'>It appears you don't have Adobe Reader or PDF support in this web
    browser. <a href='$file' style='display:block; margin:30px 0;text-align:center;color:#f7781b;'>Click here to download the PDF</a></p>
  </body>"; 
   }
   
    
   function msds_pdf(){  
   	$goods_id=I('goods_id','','trim');
   	if(empty($goods_id)){
   		exit('Error Param.');
   	}
   	$file=$this->_goods_mod->getFieldByGoods_id($goods_id,'msds');
   	$file='/'.$file; 
   	 echo '<script type="text/javascript" src="/Public/home/js/pdfobject.js"></script>';
   	 echo '<script> 
   	 		window.onload = function (){ 
           var success = new PDFObject({ url: "'.$file.'" }).embed();
      };</script>';
   	 echo  "<body>
    <p style='margin:100px 0 0 00;text-align:center; font-family:Arial, Helvetica, sans-serif;'>It appears you don't have Adobe Reader or PDF support in this web
    browser. <a href='$file' style='display:block; margin:30px 0;text-align:center; color:#f7781b;'>Click here to download the PDF</a></p>
  </body>"; 
   }  
}


?>