Hotel Booking API Documentation
📋 Общая информация
Базовый URL: http://127.0.0.1:8000/api

Формат данных: JSON

Авторизация: Bearer Token (Sanctum)

🔐 Аутентификация
Регистрация нового пользователя
http
POST /api/register
Тело запроса:

json
{
    "name": "Имя",
    "surname": "Фамилия",
    "last_name": "Отчество",           // опционально
    "email": "user@example.com",
    "phone_number": "+79991112233",
    "login": "username",
    "password": "password123",
    "password_confirmation": "password123"
}
Ответ: 201 Created

json
{
    "user": {
        "id": 1,
        "name": "Имя",
        "surname": "Фамилия",
        "email": "user@example.com",
        "login": "username"
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
}
Вход в систему
http
POST /api/login
Тело запроса:

json
{
    "login": "username",
    "password": "password123"
}
Ответ: 200 OK

json
{
    "user": {
        "id": 1,
        "name": "Имя",
        "surname": "Фамилия",
        "email": "user@example.com",
        "login": "username",
        "role_id": 2
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
}
Информация о текущем пользователе
http
GET /api/me
Headers: Authorization: Bearer {token}
Ответ: 200 OK

json
{
    "id": 1,
    "name": "Имя",
    "surname": "Фамилия",
    "last_name": "Отчество",
    "email": "user@example.com",
    "phone": "+79991112233",
    "login": "username",
    "role_id": 2
}
Выход из системы
http
POST /api/logout
Headers: Authorization: Bearer {token}
Ответ: 200 OK

json
{
    "message": "Successfully logged out"
}
🏨 Отели
Получить список отелей (с пагинацией)
http
GET /api/hotels?page=1&per_page=15
Параметры запроса:

page - номер страницы (по умолчанию 1)

per_page - количество записей на странице (по умолчанию 15)

Ответ: 200 OK

json
{
    "data": [
        {
            "id": 1,
            "name": "Название отеля",
            "class": "5 stars",
            "address": {
                "Страна": "Россия",
                "Город": "Москва",
                "Улица": "ул. Примерная, 1"
            },
            "rooms_count": 100,
            "rating": {
                "average": 4.5,
                "total": 120
            }
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 10,
        "per_page": 15,
        "total": 150,
        "next_page_url": "/api/hotels?page=2",
        "prev_page_url": null
    }
}
Получить детальную информацию об отеле
http
GET /api/hotels/{id}
Ответ: 200 OK

json
{
    "data": {
        "id": 1,
        "name": "Название отеля",
        "class": "5 stars",
        "address": {
            "Страна": "Россия",
            "Город": "Москва",
            "Улица": "ул. Примерная, 1"
        },
        "rooms": [
            {
                "id": 101,
                "number": "101",
                "floor": 1,
                "room_classes": {
                    "id": 1,
                    "name": "Standard",
                    "price_per_day": 5000
                }
            }
        ],
        "reviews_count": 120,
        "reviews_avg_rating": 4.5
    },
    "average_rating": 4.5
}
Получить отзывы об отеле
http
GET /api/hotels/{hotelId}/reviews?page=1&per_page=10&sort=newest
Параметры запроса:

page - номер страницы

per_page - количество на странице

sort - сортировка (newest, oldest, highest, lowest)

Ответ: 200 OK

json
{
    "hotel_id": 1,
    "average_rating": 4.5,
    "total_reviews": 120,
    "rating_distribution": {
        "1": 5,
        "2": 10,
        "3": 15,
        "4": 30,
        "5": 60
    },
    "data": [
        {
            "id": 1,
            "user_name": "Иван Иванов",
            "rating": 5,
            "coment": "Отличный отель!",
            "created_at": "2026-03-17 10:00:00"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 12,
        "per_page": 10,
        "total": 120
    }
}
🚪 Комнаты
Поиск свободных комнат
http
GET /api/rooms/available?date=2026-03-20&start_time=10:00:00&end_time=12:00:00
Параметры запроса:

date - дата (Y-m-d)

start_time - время начала (H:i:s)

end_time - время окончания (H:i:s)

filters[hotel_id] - фильтр по отелю (опционально)

filters[class_id] - фильтр по классу комнаты (опционально)

filters[floor] - фильтр по этажу (опционально)

filters[min_price] - минимальная цена (опционально)

filters[max_price] - максимальная цена (опционально)

sort_by - сортировка (price, floor, number)

sort_direction - направление (asc, desc)

per_page - количество на странице

page - номер страницы

Ответ: 200 OK

json
{
    "data": [
        {
            "id": 101,
            "number": "101",
            "floor": 1,
            "hotel_id": 1,
            "hotel_name": "Название отеля",
            "hotel_address": {
                "Страна": "Россия",
                "Город": "Москва",
                "Улица": "ул. Примерная, 1"
            },
            "class_id": 1,
            "class_name": "Standard",
            "price_per_day": 5000
        }
    ],
    "filters": {
        "floor": 1,
        "max_price": 10000
    },
    "date": "2026-03-20",
    "time_range": {
        "start": "10:00:00",
        "end": "12:00:00"
    },
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 75
    }
}
Получить расписание комнаты
http
GET /api/rooms/{roomId}/schedule?start_date=2026-03-20&end_date=2026-03-27
Параметры запроса:

start_date - начальная дата

end_date - конечная дата

Ответ: 200 OK

json
[
    {
        "id": 101,
        "start": "2026-03-20 10:00:00",
        "end": "2026-03-20 12:00:00",
        "user_name": "Иван Иванов",
        "user_id": 1,
        "status": "active",
        "status_id": 1
    }
]
📅 Бронирования
Создать бронирование
http
POST /api/bookings
Headers: Authorization: Bearer {token}
Тело запроса:

json
{
    "room_id": 101,
    "booking_start": "2026-03-20 10:00:00",
    "booking_end": "2026-03-20 12:00:00"
}
Ответ: 201 Created

json
{
    "id": 1,
    "room_id": 101,
    "room_number": "101",
    "hotel_id": 1,
    "hotel_name": "Название отеля",
    "room_class": "Standard",
    "booking_start": "2026-03-20 10:00:00",
    "booking_end": "2026-03-20 12:00:00",
    "status": "active",
    "status_id": 1,
    "created_at": "2026-03-17T10:00:00.000000Z"
}
Получить мои бронирования
http
GET /api/bookings/my?page=1&per_page=15&status=active
Headers: Authorization: Bearer {token}
Параметры запроса:

status - фильтр по статусу (active, completed, cancelled)

Ответ: 200 OK

json
{
    "data": [
        {
            "id": 1,
            "room_id": 101,
            "room_number": "101",
            "hotel_id": 1,
            "hotel_name": "Название отеля",
            "hotel_address": {
                "Страна": "Россия",
                "Город": "Москва",
                "Улица": "ул. Примерная, 1"
            },
            "room_class": "Standard",
            "price_per_day": 5000,
            "booking_start": "2026-03-20 10:00:00",
            "booking_end": "2026-03-20 12:00:00",
            "status": "active",
            "status_id": 1,
            "created_at": "2026-03-17T10:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
Отменить бронирование (пользователь)
http
PUT /api/bookings/{id}/cancel
Headers: Authorization: Bearer {token}
Ответ: 200 OK

json
{
    "message": "Booking cancelled successfully",
    "booking_id": 1,
    "status": "cancelled_by_user"
}
Получить завершенные бронирования (доступные для отзыва)
http
GET /api/bookings/completed
Headers: Authorization: Bearer {token}
Ответ: 200 OK

json
{
    "data": [
        {
            "id": 1,
            "booking_id": 1,
            "hotel_id": 1,
            "hotel_name": "Название отеля",
            "room_id": 101,
            "room_number": "101",
            "room_class": "Standard",
            "booking_start": "2026-03-16 10:00:00",
            "booking_end": "2026-03-16 12:00:00"
        }
    ],
    "pagination": {
        "current_page": 1,
        "last_page": 1,
        "per_page": 15,
        "total": 1
    }
}
⭐ Отзывы
Создать отзыв
http
POST /api/reviews
Headers: Authorization: Bearer {token}
Тело запроса:

json
{
    "hotel_id": 1,
    "booking_room_id": 1,
    "coment": "Отличный отель! Прекрасный сервис, чистые номера, вежливый персонал.",
    "rating": 5
}
Ответ: 201 Created

json
{
    "message": "Review created successfully",
    "review": {
        "id": 1,
        "hotel_id": 1,
        "booking_room_id": 1,
        "coment": "Отличный отель! Прекрасный сервис, чистые номера, вежливый персонал.",
        "rating": 5,
        "created_at": "2026-03-17T10:00:00.000000Z",
        "updated_at": "2026-03-17T10:00:00.000000Z"
    }
}
👑 Административные эндпойнты
Отмена бронирования администратором
http
PUT /api/admin/bookings/{id}/cancel
Headers: Authorization: Bearer {token} (admin only)
Ответ: 200 OK

json
{
    "message": "Booking cancelled by admin successfully",
    "booking_id": 1,
    "status": "cancelled_by_admin"
}
📊 Статусы бронирований
ID	Статус	Описание
1	active	Активное бронирование
2	cancelled_by_admin	Отменено администратором
3	cancelled_by_user	Отменено пользователем
4	completed	Завершено (доступно для отзыва)
🔄 Примеры использования
Полный цикл бронирования
bash
# 1. Авторизация
curl -X POST /api/login -d '{"login":"user","password":"pass"}'

# 2. Поиск свободных комнат
curl "/api/rooms/available?date=2026-03-20&start_time=10:00:00&end_time=12:00:00"

# 3. Создание бронирования
curl -X POST /api/bookings -H "Authorization: Bearer {token}" -d '{
    "room_id": 101,
    "booking_start": "2026-03-20 10:00:00",
    "booking_end": "2026-03-20 12:00:00"
}'

# 4. Просмотр моих бронирований
curl -H "Authorization: Bearer {token}" /api/bookings/my

# 5. Отмена бронирования
curl -X PUT /api/bookings/1/cancel -H "Authorization: Bearer {token}"
Создание отзыва после завершения бронирования
bash
# 1. Получить завершенные бронирования
curl -H "Authorization: Bearer {token}" /api/bookings/completed

# 2. Создать отзыв
curl -X POST /api/reviews -H "Authorization: Bearer {token}" -d '{
    "hotel_id": 1,
    "booking_room_id": 1,
    "coment": "Отличный сервис!",
    "rating": 5
}'

# 3. Проверить отзывы об отеле
curl /api/hotels/1/reviews
⚠️ Коды ошибок
Код	Описание
400	Bad Request - неверный формат запроса
401	Unauthenticated - требуется авторизация
403	Forbidden - недостаточно прав
404	Not Found - ресурс не найден
422	Unprocessable Entity - ошибка валидации
500	Internal Server Error - внутренняя ошибка сервера
Пример ошибки валидации (422)
json
{
    "errors": {
        "time": [
            "This room is already booked for the selected time period"
        ]
    }
}
🚀 Быстрый старт
Получить токен:

bash
TOKEN=$(curl -s -X POST /api/login -d '{"login":"testuser","password":"password123"}' | jq -r '.token')
Сохранить токен для последующих запросов:

bash
curl -H "Authorization: Bearer $TOKEN" /api/me
Найти свободную комнату:

bash
curl "/api/rooms/available?date=2026-03-20&start_time=10:00:00&end_time=12:00:00"
Забронировать:

bash
curl -X POST /api/bookings -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d '{
    "room_id": 101,
    "booking_start": "2026-03-20 10:00:00",
    "booking_end": "2026-03-20 12:00:00"
}'
📝 Примечания
Все даты и время передаются в формате Y-m-d H:i:s

Пагинация доступна для всех списковых эндпойнтов

Для административных эндпойнтов требуется роль администратора (role_id = 1)

Токен должен передаваться в заголовке Authorization: Bearer {token}

После выхода из системы токен становится недействительным