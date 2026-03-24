<?php
// app/Traits/UserHasRole.php

namespace App\Traits;

trait UserHasRole
{
    /**
     * Проверка, является ли пользователь администратором
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->client && $this->client->role_id === 1;
    }
    
    /**
     * Проверка, имеет ли пользователь определенную роль
     *
     * @param int|string $role
     * @return bool
     */
    public function hasRole($role): bool
    {
        if (!$this->client || !$this->client->role_id) {
            return false;
        }
        
        if (is_int($role)) {
            return $this->client->role_id === $role;
        }
        
        if (is_string($role)) {
            return $this->client->role && $this->client->role->name === $role;
        }
        
        return false;
    }
    
    /**
     * Получение названия роли пользователя
     *
     * @return string|null
     */
    public function getRoleName(): ?string
    {
        return $this->client && $this->client->role 
            ? $this->client->role->name 
            : null;
    }
}