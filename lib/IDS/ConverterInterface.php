<?php

namespace IDS;

interface ConverterInterface
{
    public function convert(string $value): string;
}
