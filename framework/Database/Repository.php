<?php

namespace Docile\Database;

abstract class Repository extends DBRepository
{
    abstract public static function up();
}
