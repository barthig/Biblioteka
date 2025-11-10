<?php
require __DIR__ . '/../vendor/autoload.php';

$fine = new App\Entity\Fine();
$fine->setAmount('5.00');
echo $fine->getAmount(), PHP_EOL;
