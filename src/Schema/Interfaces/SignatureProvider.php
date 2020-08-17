<?php


namespace SilverStripe\GraphQL\Schema\Interfaces;


interface SignatureProvider
{
    public function getSignature(): string;
}
