<?php

require dirname(__FILE__).'/Browscap.php';

class DokuBrowscap extends Browscap {

    protected function _getRemoteData($url){
        $http = new DokuHTTPClient($url);
        $file = $http->get($url);
        if(!$file)
            throw new Browscap_Exception('Your server can\'t connect to external resources. Please update the file manually.');
        return $file;
    }
}
