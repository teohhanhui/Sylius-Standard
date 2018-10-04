<?php

declare(strict_types=1);

namespace App\Security\User;

use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    private $shopUserProvider;
    private $adminUserProvider;

    public function __construct(UserProviderInterface $shopUserProvider, UserProviderInterface $adminUserProvider)
    {
        $this->shopUserProvider = $shopUserProvider;
        $this->adminUserProvider = $adminUserProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username): UserInterface
    {
        $user = null;

        try {
            $user = $this->shopUserProvider->loadUserByUsername($username);
        } catch (UsernameNotFoundException $e) {
        }

        if (null === $user) {
            try {
                $user = $this->adminUserProvider->loadUserByUsername($username);
            } catch (UsernameNotFoundException $e) {
            }
        }

        if (null === $user) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $username)
            );
        }

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        $reloadedUser = null;

        try {
            $reloadedUser = $this->shopUserProvider->refreshUser($user);
        } catch (UnsupportedUserException $e) {
        } catch (UsernameNotFoundException $e) {
        }

        if (null === $reloadedUser) {
            try {
                $reloadedUser = $this->adminUserProvider->refreshUser($user);
            } catch (UnsupportedUserException $e) {
            } catch (UsernameNotFoundException $e) {
            }
        }

        return $reloadedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsClass($class): bool
    {
        return is_a($class, SyliusUserInterface::class, true);
    }
}
