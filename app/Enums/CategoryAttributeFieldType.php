<?php

namespace App\Enums;

enum CategoryAttributeFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Select = 'select';
    case Boolean = 'boolean';
}
