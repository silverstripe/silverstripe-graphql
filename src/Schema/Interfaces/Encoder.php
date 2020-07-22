<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface Encoder
{
    public function encode(): string;
}
