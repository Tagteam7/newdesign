<?php
include_once("functions/config.inc.php");
still_logged_in("r");
$tab = 1;
$cart = get_cart_summary();
$id = "";
$prodinfo = array();
$csiimg = array();
$dciimg = array();
$item = array();
$popup = false;
if ($_SESSION['hideprice'] == "1") {
  $hideprice = true;
} else {
  $hideprice = false;
}
$userinfo = get_web_profile();
$shipviacodes = get_shipping_options("1");

foreach ($userinfo as $key => $value) {
  #####################################################
  # pull out the available ship methods for this user #
  # into a separate array.                            #
  #####################################################
  if (preg_match("/^ship_method/", $key)) {
      $list = explode('|',$value); # descr, code, allowedYN, defaultYN
      if ($list[3] == "Y") {
          $defmethod = $list[1];
      }
  }
}

if ($_SERVER['REQUEST_METHOD'] == "POST") {
  ###############################################
  # user is trying to login, ask Pick if its ok #
  # and get its session id.                     #
  ###############################################
  $_POST = $xssFilter->process($_POST);
  if (isset($_POST['id'])) {
      $id = $_POST['id'];
  }
  if (isset($_POST['qty'])) {
      $item[$id] = $_POST['qty'] . "|" . $_POST['warehouse'];
      $results = add_cart_item($item);
      $popup = true;
      $cart = get_cart_summary();
  }
} else {
  $_GET = $xssFilter->process($_GET);
  if (isset($_GET['id'])) {
      $id = $_GET['id'];
  }
}
if ($id == "") {
  header("Location: demo.php");
  exit;
}

###############################################
# first search to see if we have an exact hit #
###############################################
$searchlist = search_products($id);
$header_array     = array_shift($searchlist); # column heading titles
$header_dir_array = array_shift($searchlist); # alignment of the data cells
$search_count       = count($searchlist); # how many users
$search_col_count   = count($header_array);

if ($search_count == 1) {
  $id = $searchlist[0][0];
} else {
  header("Location: search.php?s=" . strtoupper($id));
  exit;
}

if ($id != "") {
  $prodinfo = get_product($id);
  $csiimg = get_image_list($id);
  $dciimg = get_dci_image_list($prodinfo['images']);
  $thumblist = get_image_list($id, "t");
  $prodinfo['vendorcode'] = strtoupper(substr($id, 0, 3));
  if (isset($prodinfo['unitprice'])) {
      set_recent_part($id, $prodinfo['itemdesc']);
  }
}
$vendor_logo = strtolower("/vendor/" . $prodinfo['vendorcode'] . ".jpg");
$image_file = strtolower("/images/" . $id . ".jpg");
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . "/$image_file")) {
    $image_file = $missing_image_file2;
}

//Logic for Discontinued or Obsolete items
$discontinued = false;
$discontinued_nopurchase = false;
$excludedWarehouses = array();
if ($prodinfo['status'] == "Discontinued" || $prodinfo['status'] == "Obsolete" || $prodinfo['status'] == "Do Not Order"){
    $discontinued = true;
    //If its discontinued AND we have no qty, don't even let them order it.
    if (($prodinfo['qav_1'] + $prodinfo['qav_2'] + $prodinfo['qav_3']) == 0) $discontinued_nopurchase = true;
    //Exclude any warehouses with 0 qty for a product thats discontinued.
    for ($i = 1; $i<=3; $i++){
        if ($prodinfo["qav_$i"] == 0) array_push($excludedWarehouses, "qav_$i");
    }
}

$x = "";

$cart = get_cart_summary();
if ($_SERVER['REQUEST_METHOD'] == "POST") {
  ###############################################
  # user is trying to login, ask Pick if its ok #
  # and get its session id.                     #
  ###############################################
  $_POST = $xssFilter->process($_POST);
  #####################################
  # could be deleting items from cart #
  #####################################
  if (is_array($_POST['del'])) {
      ###########################################
      # user has checked some items for removal #
      ###########################################
      remove_cart_items($_POST['del']);
      if (isset($_POST['upd'])) {
          ###############################
          # remove items from qty array #
          ###############################
          foreach ($_POST['del'] as $id) {
              unset($_POST['upd'][$id]);
          }
      }
  }

  if (is_array($_POST['upd'])) {
      ######################################
      # user is updating quantities/prices #
      ######################################
      update_cart_items($_POST['upd'], $cartname);
  }
  $cart = get_cart_summary();
  if (isset($_POST['checkout_x'])) {
      header("Location: checkout.php");
      exit;
  }
} else {
  $_GET = $xssFilter->process($_GET);
}

$mycart       = get_cart_header();
$cartcontents = get_cart_detail();
$header_array     = array_shift($cartcontents); # column heading titles
$header_dir_array = array_shift($cartcontents); # alignment of the data cells
$cart_count       = count($cartcontents); # how many product rows
$cart_col_count   = count($header_array);
?>
<?php $recent_list = get_recent_parts(); ?>
<?php $categories = get_category_list(); ?>

<html class="no-js">
<!--<![endif]-->
<head>
  <meta charset="utf-8">
  <title>Varmark - index.php</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="">
  <meta name="author" content="ProteusThemes">

<!--  = Google Fonts =  -->
<script type="text/javascript">
WebFontConfig = {
    google : {
        families : ['Open+Sans:400,700,400italic,700italic:latin,latin-ext,cyrillic', 'Pacifico::latin']
    }
};
(function() {
    var wf = document.createElement('script');
    wf.src = ('https:' == document.location.protocol ? 'https' : 'http') + '://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
    wf.type = 'text/javascript';
    wf.async = 'true';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(wf, s);
})();
</script>

<!-- Twitter Bootstrap -->
<link href="/assets/themes/nwimain/stylesheets/bootstrap.css" rel="stylesheet">
<link href="/assets/themes/nwimain/stylesheets/responsive.css" rel="stylesheet">
<link href="assets/plugins/font-awesome/css/font-awesome.min.css" rel="stylesheet">
<!-- Slider Revolution -->
<link rel="stylesheet" href="/assets/themes/nwimain/js/rs-plugin/css/settings.css" type="text/css"/>
<!-- jQuery UI -->
<link rel="stylesheet" href="/assets/themes/nwimain/js/jquery-ui-1.10.3/css/smoothness/jquery-ui-1.10.3.custom.min.css" type="text/css"/>
<!-- PrettyPhoto -->
<link rel="stylesheet" href="/assets/themes/nwimain/js/prettyphoto/css/prettyPhoto.css" type="text/css"/>
<!-- main styles -->
<link href="/assets/themes/nwimain/stylesheets/gray.css" rel="stylesheet">
<!-- Modernizr -->
<script src="/assets/themes/nwimain/js/modernizr.custom.56918.js"></script>


</head>

<body class="">
<div class="master-wrapper"> 
<?php include "assets/inc/head.php"; ?>


<div class="container">
        

        <!--  ==========  -->
        <!--  = Featured Items =  -->
        <!--  ==========  -->
        <div class="row featured-items blocks-spacer">
            <div class="span12">

                <!--  ==========  -->
                <!--  = Title =  -->
                <!--  ==========  -->
            	<div class="main-titles lined">
            	    <h2 class="title"><span class="light">Featured</span> Products</h2>
            	    <div class="arrows">
                        <a href="#" class="icon-chevron-left" id="featuredItemsLeft"></a>
                        <a href="#" class="icon-chevron-right" id="featuredItemsRight"></a>
                    </div>
            	</div>
            </div>

            <div class="span12">
                <!--  ==========  -->
                <!--  = Carousel =  -->
                <!--  ==========  -->
                <div class="carouFredSel" data-autoplay="false" data-nav="featuredItems">
                    <div class="slide">
                        <div class="row">
                    	                    	
                    	
    	            	<!--  ==========  -->
    					<!--  = Product =  -->
    					<!--  ==========  -->
    	            	<div class="span4">
    	            	    <div class="product">
    	            	        <div class="product-img featured">
    	            	            <div class="picture">
    	            	        	    <a href="product.html"><img src="images/dummy/featured-products/featured-1.png" alt="" width="518" height="358" /></a>
    	            	        		<div class="img-overlay">
    	            	        		    <a class="btn more btn-primary" href="product.html">More</a>
    	            	        		    <a href="#" class="btn buy btn-danger">Buy</a>
    	            	        		</div>
    	            	            </div>
    	            	        </div>
    	            	        <div class="main-titles">
    	            	            <h4 class="title">$67</h4>
    	            	            <h5 class="no-margin"><a href="product.html">Horsefeathers 325</a></h5>
    	            	        </div>
    	            	        <p class="desc">59% Cotton Lorem Ipsum Dolor Sit Amet esed ultrices sapien nunc nam frignila</p>
    	            	        <p class="center-align stars">
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star"></span>
                    	                                	        </p>
    	            	    </div>
                	      </div> <!-- /product -->
    	            	                    	
                    	
    	            	<!--  ==========  -->
    					<!--  = Product =  -->
    					<!--  ==========  -->
    	            	<div class="span4">
    	            	    <div class="product">
    	            	        <div class="product-img featured">
    	            	            <div class="picture">
    	            	        	    <a href="product.html"><img src="images/dummy/featured-products/featured-2.png" alt="" width="518" height="358" /></a>
    	            	        		<div class="img-overlay">
    	            	        		    <a class="btn more btn-primary" href="product.html">More</a>
    	            	        		    <a href="#" class="btn buy btn-danger">Buy</a>
    	            	        		</div>
    	            	            </div>
    	            	        </div>
    	            	        <div class="main-titles">
    	            	            <h4 class="title">$112</h4>
    	            	            <h5 class="no-margin"><a href="product.html">Horsefeathers 344</a></h5>
    	            	        </div>
    	            	        <p class="desc">59% Cotton Lorem Ipsum Dolor Sit Amet esed ultrices sapien nunc nam frignila</p>
    	            	        <p class="center-align stars">
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star"></span>
                    	            <span class="icon-star"></span>
                    	            <span class="icon-star"></span>
                    	            <span class="icon-star"></span>
                    	                                	        </p>
    	            	    </div>
                	      </div> <!-- /product -->
    	            	                    	
                    	
    	            	<!--  ==========  -->
    					<!--  = Product =  -->
    					<!--  ==========  -->
    	            	<div class="span4">
    	            	    <div class="product">
    	            	        <div class="product-img featured">
    	            	            <div class="picture">
    	            	        	    <a href="product.html"><img src="images/dummy/featured-products/featured-3.png" alt="" width="518" height="358" /></a>
    	            	        		<div class="img-overlay">
    	            	        		    <a class="btn more btn-primary" href="product.html">More</a>
    	            	        		    <a href="#" class="btn buy btn-danger">Buy</a>
    	            	        		</div>
    	            	            </div>
    	            	        </div>
    	            	        <div class="main-titles">
    	            	            <h4 class="title">$61</h4>
    	            	            <h5 class="no-margin"><a href="product.html">Horsefeathers 545</a></h5>
    	            	        </div>
    	            	        <p class="desc">59% Cotton Lorem Ipsum Dolor Sit Amet esed ultrices sapien nunc nam frignila</p>
    	            	        <p class="center-align stars">
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	                                	        </p>
    	            	    </div>
                	      </div> <!-- /product -->
    	            	                    	
                    	            	        </div>
            	    </div>
            	    <div class="slide">
            	        <div class="row">
                    	
    	            	<!--  ==========  -->
    					<!--  = Product =  -->
    					<!--  ==========  -->
    	            	<div class="span4">
    	            	    <div class="product">
    	            	        <div class="product-img featured">
    	            	            <div class="picture">
    	            	        	    <a href="product.html"><img src="images/dummy/featured-products/featured-1.png" alt="" width="518" height="358" /></a>
    	            	        		<div class="img-overlay">
    	            	        		    <a class="btn more btn-primary" href="product.html">More</a>
    	            	        		    <a href="#" class="btn buy btn-danger">Buy</a>
    	            	        		</div>
    	            	            </div>
    	            	        </div>
    	            	        <div class="main-titles">
    	            	            <h4 class="title">$75</h4>
    	            	            <h5 class="no-margin"><a href="product.html">Horsefeathers 285</a></h5>
    	            	        </div>
    	            	        <p class="desc">59% Cotton Lorem Ipsum Dolor Sit Amet esed ultrices sapien nunc nam frignila</p>
    	            	        <p class="center-align stars">
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star"></span>
                    	            <span class="icon-star"></span>
                    	                                	        </p>
    	            	    </div>
                	      </div> <!-- /product -->
    	            	                    	
                    	
    	            	<!--  ==========  -->
    					<!--  = Product =  -->
    					<!--  ==========  -->
    	            	<div class="span4">
    	            	    <div class="product">
    	            	        <div class="product-img featured">
    	            	            <div class="picture">
    	            	        	    <a href="product.html"><img src="images/dummy/featured-products/featured-2.png" alt="" width="518" height="358" /></a>
    	            	        		<div class="img-overlay">
    	            	        		    <a class="btn more btn-primary" href="product.html">More</a>
    	            	        		    <a href="#" class="btn buy btn-danger">Buy</a>
    	            	        		</div>
    	            	            </div>
    	            	        </div>
    	            	        <div class="main-titles">
    	            	            <h4 class="title">$68</h4>
    	            	            <h5 class="no-margin"><a href="product.html">Horsefeathers 557</a></h5>
    	            	        </div>
    	            	        <p class="desc">59% Cotton Lorem Ipsum Dolor Sit Amet esed ultrices sapien nunc nam frignila</p>
    	            	        <p class="center-align stars">
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star"></span>
                    	            <span class="icon-star"></span>
                    	            <span class="icon-star"></span>
                    	                                	        </p>
    	            	    </div>
                	      </div> <!-- /product -->
    	            	                    	
                    	
    	            	<!--  ==========  -->
    					<!--  = Product =  -->
    					<!--  ==========  -->
    	            	<div class="span4">
    	            	    <div class="product">
    	            	        <div class="product-img featured">
    	            	            <div class="picture">
    	            	        	    <a href="product.html"><img src="images/dummy/featured-products/featured-3.png" alt="" width="518" height="358" /></a>
    	            	        		<div class="img-overlay">
    	            	        		    <a class="btn more btn-primary" href="product.html">More</a>
    	            	        		    <a href="#" class="btn buy btn-danger">Buy</a>
    	            	        		</div>
    	            	            </div>
    	            	        </div>
    	            	        <div class="main-titles">
    	            	            <h4 class="title">$89</h4>
    	            	            <h5 class="no-margin"><a href="product.html">Horsefeathers 360</a></h5>
    	            	        </div>
    	            	        <p class="desc">59% Cotton Lorem Ipsum Dolor Sit Amet esed ultrices sapien nunc nam frignila</p>
    	            	        <p class="center-align stars">
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star stars-clr"></span>
                    	            <span class="icon-star"></span>
                    	                                	        </p>
    	            	    </div>
                	      </div> <!-- /product -->
    	            	    	            	</div>
                	</div>
                </div> <!-- /carousel -->
            </div>

        </div>
    </div>

<!-- ./container-fluid -->
<?php include('assets/inc/foot.php'); ?>
</body>
</html>
