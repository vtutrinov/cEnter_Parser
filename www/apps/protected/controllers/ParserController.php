<?php

/**
 * ParserController
 *
 * @author Slava Tutrinov
 */
class ParserController extends Controller {
    
    public function actionIndex() {
        
    }
    
    public function actionStart() {
        if (Yii::app()->getRequest()->isPostRequest) {
            $path = Yii::app()->basePath;
            $logFile = Yii::app()->getRuntimePath()."/parser.log";
            $command = "/usr/bin/php ".$path."/yiic parser start >> ".$logFile." 2>&1 &";
            exec($command);
            $this->redirect(array("/site/"));
        } else {
            $this->render('parser_start');
        }
    }
    
}

?>
