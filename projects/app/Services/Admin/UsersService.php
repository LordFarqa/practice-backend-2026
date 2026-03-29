<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Client;
use App\Models\BookingRooms;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsersService
{
    /**
     * Получить всех пользователей с их клиентами
     *
     * @return array
     */
    public function getUsers(): array
    {
        return User::with('client')
            ->get()
            ->map(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'login' => $user->client?->login
                ];
            })
            ->toArray();
    }

    /**
     * Получить пользователя по логину
     *
     * @param string $login
     * @return array|null
     */
    public function getUserByLogin(string $login): ?array
    {
        /** @var User|null $user */
        $user = User::whereHas('client', function ($query) use ($login) {
            $query->where('login', $login);
        })->with('client')->first();

        if (!$user || !$user->client) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'login' => $user->client->login
        ];
    }

    /**
     * Создать нового пользователя
     *
     * @param array $data
     * @return array|null
     * @throws ValidationException
     */
    public function createUser(array $data): ?array
    {
        if (Client::where('login', $data['login'])->exists()) {
            throw ValidationException::withMessages([
                'login' => 'Login already exists'
            ]);
        }

        /** @var User $user */
        $user = User::create([
            'name' => $data['name'],
            'surname' => $data['surname'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'],
            'phone_number' => $data['phone_number']
        ]);

        Client::create([
            'user_id' => $user->id,
            'login' => $data['login'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'] ?? 2
        ]);

        return $this->getUserByLogin($data['login']);
    }

    /**
     * Обновить пользователя
     *
     * @param int $id
     * @param array $data
     * @return array|null
     */
    public function updateUser(int $id, array $data): ?array
    {
        /** @var User|null $user */
        $user = User::with('client')->find($id);

        if (!$user || !$user->client) {
            return null;
        }

        // Обновляем данные пользователя
        $user->update([
            'name' => $data['name'] ?? $user->name,
            'surname' => $data['surname'] ?? $user->surname,
            'last_name' => $data['last_name'] ?? $user->last_name,
            'email' => $data['email'] ?? $user->email,
            'phone_number' => $data['phone_number'] ?? $user->phone_number
        ]);

        // Обновляем данные клиента
        if (isset($data['login'])) {
            $user->client->login = $data['login'];
        }

        if (isset($data['password'])) {
            $user->client->password = Hash::make($data['password']);
        }

        if (isset($data['role_id'])) {
            $user->client->role_id = $data['role_id'];
        }

        $user->client->save();

        return $this->getUserByLogin($user->client->login);
    }

    /**
     * Удалить пользователя
     *
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        /** @var User|null $user */
        $user = User::with('client')->find($id);

        if (!$user) {
            return false;
        }

        // Удаляем связанного клиента
        if ($user->client) {
            $user->client()->delete();
        }
        
        // Удаляем пользователя
        return (bool) $user->delete();
    }

    /**
     * Найти пользователя по ID
     *
     * @param int $id
     * @return User|null
     */
    public function findUser(int $id): ?User
    {
        /** @var User|null $user */
        $user = User::with('client')->find($id);
        return $user;
    }

    /**
     * Получить всех пользователей с пагинацией и поиском
     *
     * @param int $perPage
     * @param string|null $search
     * @return LengthAwarePaginator
     */
    public function getAllUsers(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        $query = User::with('client', 'client.role')
            ->select('users.*');
        
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('surname', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhereHas('client', function ($cq) use ($search) {
                        $cq->where('login', 'like', "%$search%");
                    });
            });
        }
        
        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Получить пользователя по ID с дополнительной информацией
     *
     * @param int $id
     * @return array|null
     */
    public function getUserById(int $id): ?array
    {
        /** @var User|null $user */
        $user = User::with('client', 'client.role')->find($id);
        
        if (!$user || !$user->client) {
            return null;
        }
        
        // Исправлено: используем bookings() вместо booking()
        $bookingsCount = $user->bookings()->count();
        
        return [
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'login' => $user->client->login,
            'role_id' => $user->client->role_id,
            'role_name' => $user->client->role?->name ?? null,
            'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
            'bookings_count' => $bookingsCount
        ];
    }

    /**
     * Получить пользователя с его бронированиями
     *
     * @param int $id
     * @return array|null
     */
    /**
 * Получить пользователя с его бронированиями
 *
 * @param int $id
 * @return array|null
 */
public function getUserWithBookings(int $id): ?array
{
    /** @var User|null $user */
    $user = User::with(['client', 'bookings.room.hotel'])->find($id);
    
    if (!$user) {
        return null;
    }
    
    return [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'surname' => $user->surname,
            'email' => $user->email,
            'phone' => $user->phone_number,
        ],
        'bookings' => $user->bookings->map(function (BookingRooms $booking) {
            return [
                'id' => $booking->id,
                'room_number' => $booking->room->number ?? null,
                'hotel_name' => $booking->room->hotel->name ?? null,
                // Используем аксессоры или проверяем существование полей
                'check_in' => $booking->check_in ?? $booking->check_in_date ?? null,
                'check_out' => $booking->check_out ?? $booking->check_out_date ?? null,
                'status' => $booking->status ?? null,
                'total_price' => $booking->total_price ?? $booking->total_amount ?? $booking->price ?? 0
            ];
        })
    ];
}

    /**
     * Проверить существование пользователя по email
     *
     * @param string $email
     * @return bool
     */
    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Проверить существование пользователя по телефону
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function phoneExists(string $phoneNumber): bool
    {
        return User::where('phone_number', $phoneNumber)->exists();
    }
}