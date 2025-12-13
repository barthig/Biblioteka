<?php

$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
];

if (class_exists(Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class)) {
    $bundles[Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class] = ['all' => true];
}

if (class_exists(Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class)) {
    $bundles[Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class] = ['dev' => true, 'test' => true];
}

if (class_exists(Symfony\Bundle\MakerBundle\MakerBundle::class)) {
    $bundles[Symfony\Bundle\MakerBundle\MakerBundle::class] = ['dev' => true];
}

if (class_exists('Symfony\\Bundle\\SerializerBundle\\SerializerBundle')) {
    $bundles['Symfony\\Bundle\\SerializerBundle\\SerializerBundle'] = ['all' => true];
}

if (class_exists(Nelmio\ApiDocBundle\NelmioApiDocBundle::class)) {
    $bundles[Nelmio\ApiDocBundle\NelmioApiDocBundle::class] = ['all' => true];
}

if (class_exists(Nelmio\CorsBundle\NelmioCorsBundle::class)) {
    $bundles[Nelmio\CorsBundle\NelmioCorsBundle::class] = ['all' => true];
}

return $bundles;
