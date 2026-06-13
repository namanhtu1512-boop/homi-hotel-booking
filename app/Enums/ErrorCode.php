<?php

namespace App\Enums;

enum ErrorCode: string
{
    case VALIDATION_ERROR   = 'VALIDATION_ERROR';
    case UNAUTHENTICATED    = 'UNAUTHENTICATED';
    case UNAUTHORIZED       = 'UNAUTHORIZED';
    case ACCOUNT_LOCKED     = 'ACCOUNT_LOCKED';
    case NOT_FOUND          = 'NOT_FOUND';
    case METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    case TOO_MANY_REQUESTS  = 'TOO_MANY_REQUESTS';
    case SERVER_ERROR       = 'SERVER_ERROR';
}
