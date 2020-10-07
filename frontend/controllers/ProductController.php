<?php
require_once "controllers/Controller.php";
require_once "models/Product.php";
require_once "models/Category.php";
require_once "models/Size.php";
require_once "models/Color.php";
require_once "models/Pagination.php";
require_once "models/Image.php";
require_once "helpers/Helper.php";
require_once "models/MenuTree.php";
class ProductController extends Controller{
    public function addlist(){
        $id=$_GET['id'];
        $category_model=new Category();
        $category=$category_model->getCategoryById($id);

        $menu=[
            'id'=>$category['id'],
            'name'=>$category['name'],
            'parent_id'=>$category['parent_id'],
        ];
        $_SESSION['menu']=$menu;
        $name=$_SESSION['menu']['name'];
        $name_slug=Helper::getSlug($name);
        $url_redirect=$_SERVER['SCRIPT_NAME']."/listProduct/".$name_slug;
        header("Location: $url_redirect");
    }
    public function showAll(){
        $params=[];
        $str_category_id='';
        $str_price='';
//        $str_size='';
//        $str_color='';
        if (isset($_GET['search'])){
            if (isset($_GET['controller'])){
                $_GET['controller']='product';
            }elseif (isset($_GET['action'])){
                $_GET['action']='showAll';
            }
            $params['search']=$_GET['search'];

        }
        $id=$_SESSION['menu']['id'];
        if (isset($_SESSION['menu'])){
            $params['id']=$id;
        }
        if(isset($_POST['filter'])){
            if(isset($_POST['category'])){
                $str_category_id=implode(',',$_POST['category']);
                $str_category_id =" ($str_category_id)";
                $str_category_id="products.category_id IN $str_category_id";
                $params['str_category_id']=$str_category_id;
            }
//            var_dump($str_category_id);
            if(isset($_POST['price'])){
                switch ($_POST['price']){
                    case 1: $str_price =" OR products.price < 200000";break;
                    case 2: $str_price =" OR (products.price BETWEEN 200000 AND 500000)";break;
                    case 3:$str_price =" OR (products.price >500000)";break;
                }
                $str_price=substr($str_price,3);
                $str_price=" ($str_price)";
                $params['str_price']=$str_price;
//                var_dump($str_price);
            }
        }
        $category_model=new Category();
        $categories=$category_model->getMenuAll();
        $product_model=new Product();
        $total=$product_model->countTotal();
        $page=1;
        if(isset($_GET['page'])){$page=$_GET['page'];}
        $limit=9;
        $start=($page-1)*$limit;
        $param_pagination=[
            'total'=>$total,
            'limit'=>$limit,
            'id'=>$id,
            'controller'=>'product',
            'action'=>'showAll',
            'full_mode'=>false,
        ];
        $params['limit']=$limit;
        $params['start']=$start;
//        print_r($param_pagination);
        $products=$product_model->getAll($params);
        $pagination=new Pagination($param_pagination);
        $pages=$pagination->getPagination();
        $this->content=$this->render('views/products/show_all.php',[
            'categories'=>$categories,
            'products'=>$products,
            'pages'=>$pages
        ]);
        require_once ('views/layouts/main.php');
    }
    public function show_list(){

        $query_additional='';
        if (isset($_GET['search'])){
            $query_additional .= '&search=' .$_GET['search'];
        }
        $id=$_SESSION['menu']['id'];
        $category_model=new Category();
        $categories=$category_model->getMenuAll();
        $parent_id=0;
        $newString='';
        $str_parent='';
        foreach ($categories as $cate){
            if ($cate['parent_id']==$parent_id){
                $str_parent .=$cate['id'] .',';
                $newParent = $cate['id'];
                print_r($newParent);
                foreach ($categories as $value){
                    if ($value['parent_id']==$newParent){
                        $newString .=$value['id'] .",";
                    }
                }
            }

        }
        $newString= rtrim($newString,',');
        $str_parent=rtrim($str_parent,',');
        $newString="($newString)";

//        echo $newString, $str_parent;

        $product_model=new Product();
        $total=$product_model->countTotal();
        $limit=9;
        $param_pagination=[
            'total'=>$total,
            'limit'=>$limit,
            'id'=>$id,
            'newString'=>$newString,
            'str_parent'=>$str_parent,
            'controller'=>'product',
            'action'=>'show_list',
            'full_mode'=>false,
            'query_additional'=>$query_additional,
            'page' => isset($_GET['page']) ? $_GET['page'] : 1
        ];
//        print_r($param_pagination);
        $products=$product_model->getAllPagination($param_pagination);
        $pagination=new Pagination($param_pagination);
        $pages=$pagination->getPagination();
        $this->content=$this->render('views/products/show_list.php',[
            'categories'=>$categories,
            'products'=>$products,
            'pages'=>$pages
        ]);
        require_once ('views/layouts/main.php');
    }
//    public function showAll(){
//        $product_model=new Product();
//        $count_total=$product_model->countTotal();
//        $query_additional='';
//        if (isset($_GET['search'])){
//            $query_additional .= '&search=' .$_GET['search'];
//        }
//        $arr_params=[
//            'total'=>$count_total,
//            'limit'=>9,
//            'query_string'=>'page',
//            'controller'=>'product',
//            'action'=>'showAll',
//            'full_mode'=>false,
//            'query_additional'=>$query_additional,
//            'page'=>isset($_GET['page'])?$_GET['page']:1
//        ];
//        $products=$product_model->getAllPagination($arr_params);
//        $pagination=new Pagination($arr_params);
//        $pages=$pagination->getPagination();
//        $category_model=new Category();
//        $categories=$category_model->getAll();
//        $this->content=$this->render('views/products/show_all.php',[
//            'categories'=>$categories,
//            'products'=>$products,
//            'pages'=>$pages
//        ]);
//        require_once ('views/layouts/main.php');
//    }
    public function detail(){
        $id=$_GET['id'];
        $product_model=new Product();
        $product=$product_model->getById($id);
        $product_choose=$product_model->choose($id);
        $size_model=new Size();
        $sizes=$size_model->getAll();
        $color_model=new Color();
        $colors=$color_model->getAll();
        $image_model=new Image();
        $images=$image_model->getAll();
        $category_model=new Category();
        $categories=$category_model->getMenuAll();
        $output=[
            'categories'=>$categories,
            'product'=>$product,
            'sizes'=>$sizes,
            'colors'=>$colors,
            'images'=>$images,
            'product_choose'=>$product_choose
        ];
        $this->content=$this->render('views/products/detail.php',$output);
        require_once "views/layouts/main.php";
    }
}