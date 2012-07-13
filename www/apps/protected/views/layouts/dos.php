
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title><?php echo CHtml::encode($this->pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="<?php echo Yii::app()->request->baseUrl; ?>/css/bootstrap/css/bootstrap.css" rel="stylesheet">
    <style type="text/css">
      body {
        padding-top: 60px;
        padding-bottom: 40px;
      }
      .sidebar-nav {
        padding: 9px 0;
      }
    </style>
    <link href="<?php echo Yii::app()->request->baseUrl; ?>/css/bootstrap-responsive.css" rel="stylesheet">

    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

    <!-- Le fav and touch icons -->
    <script type="text/javascript" src="<?php echo Yii::app()->request->baseUrl;?>/css/bootstrap/js/bootstrap.min.js"></script>
    <?php
        Yii::app()->getClientScript()->registerCoreScript('jquery.ui');
        Yii::app()->clientScript->registerCssFile(
                Yii::app()->clientScript->getCoreScriptUrl().'/jui/css/base/jquery-ui.css'
        );
    ?>
  </head>
  <body>

    <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="#">Dostavka</a>
          <div class="btn-group pull-right">
            <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
              <i class="icon-user"></i> Username
              <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="#">Profile</a></li>
              <li class="divider"></li>
              <li><a href="#">Sign Out</a></li>
            </ul>
          </div>
          <div class="nav-collapse">
<!--            <ul class="nav">
              <li class="active"><a href="#">Home</a></li>
              <li><a href="#about">About</a></li>
              <li><a href="#contact">Contact</a></li>
            </ul>-->
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class="container-fluid">
      <div class="row-fluid">
        <div class="span3">
          <div class="well sidebar-nav">
            <?php 
            $this->widget('zii.widgets.CMenu',array(
                'items'=>array(
                    array('label'=>'Меню', 'itemOptions'=>array('class' => 'nav-header')),
                    array('label'=>'Запустить парсер товаров с Enter.RU и Yandex.Market', 'url'=>array('/parser/start')),
                    array('label'=>'Запустить поиск соответствий', 'url'=>array('/search')),
                    array('label'=>'Прайс', 'itemOptions'=>array('class' => 'nav-header')),
                    array('label'=>'Список товаров', 'url'=>array('/price/goods')),
                    array('label'=>'Загрузить новый прайс', 'url'=>array('/price/newprice')),
                ),
                'htmlOptions' => array(
                    'class' => 'nav nav-list',
                )
            ));
            ?>
          </div><!--/.well -->
        </div><!--/span-->
        <div class="span9">
            <div class="span-8">
                <?php echo $content; ?>
            </div>
        </div><!--/span-->
      </div><!--/row-->

      <hr>

      <footer>
        <p>&copy; TaxaSoftware 2012</p>
      </footer>

    </div><!--/.fluid-container-->

  </body>
</html>