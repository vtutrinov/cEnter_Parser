<?php
/**
 * Adapter_Mysql
 *
 * @author Slava Tutrinov
 */
class Adapter_Mysql implements Interface_DSNReturnable{

    public function  getDSN(stdClass $config) {
        return $config -> adapter.":host=".$config -> params -> host.";port=".$config -> params -> port.";dbname=".$config -> params -> dbname;
    }

}
?>
