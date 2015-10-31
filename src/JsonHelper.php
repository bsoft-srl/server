<?php
namespace Sideco;

class JsonHelper {

    public static function success($data, $encode = true) {

        $arr = [
            'success' => true,
            'data' => $data
        ];

        return $encode ? json_encode($arr) : $arr;
    }

    public static function fail($message, $encode = true) {

        $arr = [
            'success' => false,
            'message' => $message
        ];

        return $encode ? json_encode($arr) : $arr;
    }
}
