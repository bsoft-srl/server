<?php
namespace sideco;

class JsonHelper {

    public static function success($data, $encode = true) {
        $retval = [
            'success' => true,
            'payload' => $data
        ];

        return $encode ? json_encode($retval) : $retval;
    }

    public static function fail($message, $encode = true) {
        $retval = [
            'success' => false,
            'message' => $message
        ];

        return $encode ? json_encode($retval) : $retval;
    }
}
