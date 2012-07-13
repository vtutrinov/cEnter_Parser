<?php

/**
 * PriceController
 *
 * @author Slava Tutrinov
 */
class PriceController extends Controller {
    
    public function actionNewPrice() {
        $request = Yii::app()->getRequest();
        if ($request->isPostRequest) {
            $file = CUploadedFile::getInstanceByName('price');
            $file->saveAs('protected/data/'.$file->name);
            $path = Yii::app()->getBasePath();
            $logFile = $path."/runtime/priceLoader.log";
            $command = "/usr/bin/php ".$path."/yiic parser loadPrice >> ".$logFile."  2>&1 &";
            exec($command);
            $this->redirect(array("/site"));
        } else {
            $this->render("load");
        }
    }
    
    public function actionGoods() {
        $dataProvider = new CActiveDataProvider('Price', array(
            'pagination' => array(
                'pageSize' => 50,
            ),
        ));
        $this->render('list', array(
            'dataProvider' => $dataProvider,
        ));
    }
    
}

?>
