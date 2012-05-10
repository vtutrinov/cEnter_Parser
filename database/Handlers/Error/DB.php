<?php
/**
 * Handlers_Error_DB
 *
 * @author Slava Tutrinov
 */
class Handlers_Error_DB implements Interface_DumpHandler {

    public function  process($code, $info, $message, $query, $runtime) {
        return $code.": ".$info.". ".$message."\r\n".$query.".\r\nTime: ".$runtime;
    }

}
?>
