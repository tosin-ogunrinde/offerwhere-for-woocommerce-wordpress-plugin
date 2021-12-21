<?php

if (!defined('ABSPATH')) {
    exit;
}

class Offerwhere_HTTP_Status
{
    const OK = 200;
    const NO_CONTENT = 204;
    const NOT_FOUND = 404;
    const CONFLICT = 409;
    const PRECONDITION_FAILED = 412;
}
