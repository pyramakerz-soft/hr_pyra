<?php

namespace Modules\Users\Enums;


enum StatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Declined = 'declined';
}