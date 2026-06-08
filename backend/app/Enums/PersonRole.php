<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Role of a tracked individual within a tenant (SPEC.md §6 persons.role).
 */
enum PersonRole: string
{
    case Employee = 'employee';
    case Owner = 'owner';
    case Dependent = 'dependent';
}
