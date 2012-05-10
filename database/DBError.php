<?php
/**
 * DBError
 *
 * @author Slava Tutrinov
 */
class DBError {

    public static function handle(DB $db, $stm = null) {
        //error handling mode
        $errorHandlingMode = $db -> getErrorHandlingMode();
        //error output mode
        $errorOutputMode = $db -> getErrorOutput();
        //error dump mode
        $errorDumpMode = $db -> getDumpMode();
        //error dump handler
        $errorDumpHandler = null;
        if ($errorDumpMode & DB::DUMP_CUSTOM) {
            $errorDumpHandler = $db -> getDumpHandler();
        }
        if ($errorHandlingMode == DB::ERRMODE_EXCEPTION) {
            if ($stm instanceof PDOStatement) {
                $errorInfo = $stm -> errorInfo();
                $code = $stm -> errorCode();
                ob_start();
                $stm->debugDumpParams();
                $queryStr = ob_get_clean();
                self::handleExeptionMode($errorOutputMode, $errorInfo[2], null, $queryStr, $code, $errorDumpMode, $errorDumpHandler, $stm -> getRuntime());
            } elseif (is_string($stm)) {
                $errorInfo = $db -> errorInfo();
                $code = $db -> errorCode();
                $queryStr = $db->getQueryString();
                self::handleExeptionMode($errorOutputMode, $errorInfo[2], $stm, $queryStr, $code, $errorDumpMode, $errorDumpHandler, $db -> getRuntime());
            } elseif (is_null($stm)) {
                $errorInfo = $db -> errorInfo();
                $code = $db -> errorCode();
                $queryStr = $db->getQueryString();
                self::handleExeptionMode($errorOutputMode, $errorInfo[2], null, $queryStr, $code, $errorDumpMode, $errorDumpHandler, $db -> getRuntime());
            }
        } elseif ($errorHandlingMode == DB::ERRMODE_WARNING) {
            exit;
        }
        return;
    }

    /**
     * Return error code
     * @param DB|DBStatement $dbh
     * @return string
     */
    public static function code($dbh) {
        return $dbh->errorCode();
    }

    /**
     * Return error info
     * @param DB|DBStatement $dbh
     * @return string
     */
    public static function info($dbh) {
        return $dbh -> errorInfo();
    }

    /**
     * Return exuted query
     * @param DB|DBStatement $dbh
     * @return string
     */
    public static function query($dbh) {
        $class = get_class($dbh);
        switch ($class) {
            case 'DB':
                $query = $dbh -> getQueryString();
                break;
            case 'DBStatement':
                ob_start();
                $dbh->debugDumpParams();
                $query = ob_get_clean();
                break;
            default:
                $query = '';
                break;
        }
        return $query;
    }

    /**
     * Return query execution time
     * @param DB|DBStatement $dbh
     * @return float
     */
    public static function runtime($dbh) {
        return $dbh -> getRuntime();
    }

    private static function handleExeptionMode($errorOutputMode, $errorInfo, $userMessage = null, $queryStr, $code, $errorDumpMode, Interface_DumpHandler $errorDumpHandler = null, $runtime) {
        if ($errorOutputMode & DB::ERROR_OUTPUT_MEMORY) {
            $customHandlerIsActive = false;
            if ($errorDumpMode & DB::DUMP_CUSTOM) {
                $customHandlerIsActive = true;
                $handledMessage = $errorDumpHandler -> process($code, $errorInfo, $userMessage, $queryStr, $runtime);
                /**
                 * @todo write to log $handledMessage
                 */
            }
            if (!$customHandlerIsActive) {
                $message = array();
                if ($errorDumpMode & DB::DUMP_USERMESSAGE) {
                    $message['usermessage'] = $userMessage;
                }
                if ($errorDumpMode & DB::DUMP_QUERY) {
                    $message['query'] = $queryStr;
                }
                if ($errorDumpMode & DB::DUMP_ERRORINFO) {
                    $message['errorInfo'] = $errorInfo;
                }
                /**
                 * @todo write decorated message to log
                 */
            }
        }
        if ($errorOutputMode & DB::ERROR_OUTPUT_BROWSER) {
            $message = $errorInfo;
            if (!is_null($userMessage)) {
                $message .= $userMessage.": ".$message;
            }
            throw new PDOException($message, $code);
        }
    }

}
?>
