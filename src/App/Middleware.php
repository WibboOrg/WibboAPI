<?php

$app->add(new \CorsSlim\CorsSlim(array('origin' => getenv('APP_DOMAIN'))));