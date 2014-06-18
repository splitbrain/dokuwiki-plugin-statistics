<?php
/**
 * Defines regular expressions for the most common search engines
 */

$SEARCHENGINEINFO = array(
    'dokuwiki'   => array('DokuWiki Internal Search', wl()),

    'google'     => array('Google', 'http://www.google.com'),
    'yahoo'      => array('Yahoo!', 'http://www.yahoo.com'),
    'yandex'     => array('Яндекс (Yandex)', 'http://www.yandex.ru'),
    'naver'      => array('네이버 (Naver)', 'http://www.naver.com'),
    'baidu'      => array('百度 (Baidu)', 'http://www.baidu.com'),
    'ask'        => array('Ask', 'http://www.ask.com'),
    'babylon'    => array('Babylon', 'http://search.babylon.com'),
    'aol'        => array('AOL Search', 'http://search.aol.com'),
    'duckduckgo' => array('DuckDuckGo', 'http://duckduckgo.com'),
    'bing'       => array('Bing', 'http://www.bing.com'),
);

$SEARCHENGINES = array(

    '^(\w+\.)*google(\.co)?\.([a-z]{2,5})$' => array('google', 'q'),
    '^(\w+\.)*bing(\.co)?\.([a-z]{2,5})$'   => array('bing', 'q'),
    '^(\w+\.)*yandex(\.co)?\.([a-z]{2,5})$' => array('yandex', 'query'),
    '^(\w+\.)*yahoo\.com$'                  => array('yahoo', 'p'),
    '^search\.naver\.com$'                  => array('naver', 'query'),
    '^(\w+\.)*baidu\.com$'                  => array('baidu', 'wd', 'word', 'kw'),

    '^search\.avg\.com$'                    => array('google', 'q'),
    '^(\w+\.)*ask\.com$'                    => array('ask', 'ask', 'q', 'searchfor'),
    '^(\w+\.)*search-results\.com$'         => array('ask', 'ask', 'q', 'searchfor'),
    '^search\.babylon\.com$'                => array('babylon', 'q'),

    '^(\w+\.)*(aol)?((search|recherches?|images|suche|alicesuche)\.)aol(\.co)?\.([a-z]{2,5})$'
                                            => array('aol', 'query', 'q'),
    '^duckduckgo\.com$'                     => array('duckduckgo', 'q'),

);
