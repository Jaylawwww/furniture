<?php

namespace App\Security;

use App\Entity\User;

final class CustomerAccountHelper
{
    /**
     * True when the account is a retail customer (ROLE_USER only), not admin or staff.
     */
    public static function isCustomerOnly(User $user): bool
    {
        $rawRoles = $user->getRawRoles();

        return !\in_array('ROLE_ADMIN', $rawRoles, true)
            && !\in_array('ROLE_STAFF', $rawRoles, true);
    }
}
