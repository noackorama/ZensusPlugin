<?php
function get_route($route = '')
{
    $route = substr(parse_url($route ?: $_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen($GLOBALS['CANONICAL_RELATIVE_PATH_STUDIP']));
    if (strpos($route, 'plugins.php/') !== false) {
        $trails = explode('plugins.php/', $route);
        $pieces = explode('/', $trails[1]);
        $route = 'plugins.php/' . $pieces[0] . ($pieces[1] ? '/' . $pieces[1] : '') . ($pieces[2] ? '/' . $pieces[2] : '');
    } elseif (strpos($route, 'dispatch.php/') !== false) {
        $trails = explode('dispatch.php/', $route);
        $dispatcher = new StudipDispatcher();
        $pieces = explode('/', $trails[1]);
        foreach ($pieces as $index => $piece) {
            $trail .= ($trail ? '/' : '') . $piece;
            if ($dispatcher->file_exists($trail . '.php')) {
                $route = 'dispatch.php/' . $trail . ($pieces[$index+1] ? '/' . $pieces[$index+1] : '');
            }
        }
    }
    while (substr($route, strlen($route)-6, 6) == '/index') {
        $route = substr($route, 0, strlen($route)-6);
    }
    return $route;
}
