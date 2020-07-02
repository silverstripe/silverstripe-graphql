<?php


namespace SilverStripe\GraphQL\Schema;


interface Encoder
{
    public function encode(): string;
}
