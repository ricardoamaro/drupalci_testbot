<?php
function getContainers($type){
    $containers = glob('containers/'.$type.'/*', GLOB_ONLYDIR);
    foreach ($containers as $container) {
        $option[] = explode('/', $container)[2];
    }
    return $option;
}
