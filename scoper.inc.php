<?php

$finder = Isolated\Symfony\Component\Finder\Finder::class;

return [
    'exclude-namespaces' => ["Psr\\Http", "Psr\\Container", "Psr\\Clock", '/regex/'],
];
