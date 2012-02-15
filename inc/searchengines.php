<?php
/**
 * Defines regular expressions for the most common search engines
 */

$SEARCHENGINES = array(

    '^(\w+\.)*google(\.co)?\.([a-z]{2-5})$' => array('google','q'),
    '^(\w+\.)*bing(\.co)?\.([a-z]{2-5})$'   => array('bing','q'),
    '^(\w+\.)*yandex(\.co)?\.([a-z]{2-5})$' => array('yandex','query'),
    '^(\w+\.)*yahoo\.com$'                  => array('yahoo','p'),
    '^search\.naver\.com$'                  => array('naver','query'),
    '^(\w+\.)*baidu\.com$'                  => array('baidu','wd', 'word', 'kw'),

    '^www\.yasni\.(de|com|co.uk|ch|.at)'    => array('yasni','query'),
    '^search\.avg\.com$'                    => array('google','q'),
    '^(\w+\.)*ask\.com$'                    => array('ask','ask','q','searchfor'),
    '^(\w+\.)*search-results\.com$'         => array('ask','ask','q','searchfor'),
    '^search\.babylon\.com$'                => array('babylon','q'),

    '^(\w+\.)*(aol)?((search|recherches?|images|suche|alicesuche)\.)aol(\.co)?\.([a-z]{2-5})$'
                                            => array('aol','query', 'q'),
    '^duckduckgo\.com$'                     => array('duckduckgo','q'),

);
