<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, mixed>
 */
final class CustomerAccountVoter extends Voter
{
    public const CUSTOMER_ACCOUNT = 'CUSTOMER_ACCOUNT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::CUSTOMER_ACCOUNT;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return $user instanceof User && CustomerAccountHelper::isCustomerOnly($user);
    }
}
