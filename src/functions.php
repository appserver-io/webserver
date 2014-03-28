<?php

function getenv($env) {
    if (isset($_SERVER[$env])) {
        return $_SERVER[$env];
    }
}

function headers_sent() {
    return false;
}
