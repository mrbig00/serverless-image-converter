<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

return function ($event) {
    $converter = new \App\ImageConverter(
        $event,
        'faithcanon-dev-derivated'
    );



    return json_encode($converter->execute());
};
