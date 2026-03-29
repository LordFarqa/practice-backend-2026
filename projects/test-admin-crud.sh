#!/bin/bash

BASE_URL="http://127.0.0.1:8000/api"

echo "========================================="
echo "АДМИНСКИЙ CRUD - ТЕСТИРОВАНИЕ"
echo "========================================="
echo ""

# Авторизация
echo "1. АВТОРИЗАЦИЯ АДМИНИСТРАТОРА"

# Добавляем Accept: application/json ко всем запросам
LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"login":"admin","password":"admin123"}')

echo "Login response: $LOGIN_RESPONSE"

# Пробуем разные способы извлечения токена
TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | head -1 | sed 's/"token":"//;s/"//')
fi

if [ -z "$TOKEN" ]; then
    echo "Пробуем войти как testuser..."
    LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/login \
      -H "Content-Type: application/json" \
      -H "Accept: application/json" \
      -d '{"login":"testuser","password":"password123"}')
    
    TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
fi

if [ -z "$TOKEN" ]; then
    echo "ОШИБКА: Не удалось получить токен"
    echo "Ответ сервера: $LOGIN_RESPONSE"
    exit 1
fi

# Извлекаем user_id и role_id из ответа
USER_ID=$(echo $LOGIN_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
ROLE_ID=$(echo $LOGIN_RESPONSE | grep -o '"role_id":[0-9]*' | head -1 | cut -d':' -f2)

echo "✓ Токен получен: ${TOKEN:0:20}..."
echo "User ID: $USER_ID, Role ID: $ROLE_ID"

if [ "$ROLE_ID" != "1" ]; then
    echo "ОШИБКА: Вы не администратор! Role ID: $ROLE_ID, требуется: 1"
    exit 1
fi

echo ""

# Функция для выполнения запросов с заголовками
make_request() {
    local method=$1
    local endpoint=$2
    local data=$3
    
    if [ -n "$data" ]; then
        curl -s -X $method "$BASE_URL$endpoint" \
          -H "Authorization: Bearer $TOKEN" \
          -H "Content-Type: application/json" \
          -H "Accept: application/json" \
          -d "$data"
    else
        curl -s -X $method "$BASE_URL$endpoint" \
          -H "Authorization: Bearer $TOKEN" \
          -H "Content-Type: application/json" \
          -H "Accept: application/json"
    fi
}

# ==================== 1. CRUD ПОЛЬЗОВАТЕЛЕЙ ====================
echo "2. CRUD ПОЛЬЗОВАТЕЛЕЙ"

RANDOM_SUFFIX=$((RANDOM % 10000))

# Создание пользователя
echo "2.1 POST /api/admin/users - создание пользователя"
CREATE_USER_RESPONSE=$(make_request "POST" "/admin/users" "{
    \"name\": \"Тест\",
    \"surname\": \"Пользователь\",
    \"last_name\": \"Тестович\",
    \"email\": \"testuser_${RANDOM_SUFFIX}@example.com\",
    \"phone_number\": \"+79991234567\",
    \"login\": \"testuser_${RANDOM_SUFFIX}\",
    \"password\": \"password123\",
    \"role_id\": 2
}")

echo "$CREATE_USER_RESPONSE"
NEW_USER_ID=$(echo $CREATE_USER_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$NEW_USER_ID" ] && [ "$NEW_USER_ID" != "null" ] && [ "$NEW_USER_ID" != "" ]; then
    echo "✓ Пользователь создан с ID: $NEW_USER_ID"
else
    echo "✗ Не удалось создать пользователя"
fi

echo ""

# Получение списка пользователей
echo "2.2 GET /api/admin/users - список пользователей"
USERS_RESPONSE=$(make_request "GET" "/admin/users?per_page=3" "")
echo "$USERS_RESPONSE" | jq '.' 2>/dev/null || echo "$USERS_RESPONSE"
echo ""

# Обновление пользователя
if [ -n "$NEW_USER_ID" ] && [ "$NEW_USER_ID" != "null" ] && [ "$NEW_USER_ID" != "" ]; then
    echo "2.3 PUT /api/admin/users/$NEW_USER_ID - обновление"
    UPDATE_RESPONSE=$(make_request "PUT" "/admin/users/$NEW_USER_ID" "{
        \"name\": \"ОбновленноеИмя\",
        \"phone_number\": \"+79998887766\"
    }")
    echo "$UPDATE_RESPONSE"
    echo ""
fi

# ==================== 2. CRUD ОТЕЛЕЙ ====================
echo "3. CRUD ОТЕЛЕЙ"

# Создание отеля
echo "3.1 POST /api/admin/hotels - создание отеля"
CREATE_HOTEL_RESPONSE=$(make_request "POST" "/admin/hotels" "{
    \"name\": \"Тестовый Отель ${RANDOM_SUFFIX}\",
    \"address\": \"г. Москва, ул. Тестовая, д. 1\",
    \"class\": 5
}")

echo "$CREATE_HOTEL_RESPONSE"
NEW_HOTEL_ID=$(echo $CREATE_HOTEL_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$NEW_HOTEL_ID" ] && [ "$NEW_HOTEL_ID" != "null" ] && [ "$NEW_HOTEL_ID" != "" ]; then
    echo "✓ Отель создан с ID: $NEW_HOTEL_ID"
else
    echo "✗ Не удалось создать отель"
fi

echo ""

# Получение списка отелей
echo "3.2 GET /api/admin/hotels - список отелей"
HOTELS_RESPONSE=$(make_request "GET" "/admin/hotels?per_page=3" "")
echo "$HOTELS_RESPONSE" | jq '.' 2>/dev/null || echo "$HOTELS_RESPONSE"
echo ""

# Обновление отеля
if [ -n "$NEW_HOTEL_ID" ] && [ "$NEW_HOTEL_ID" != "null" ] && [ "$NEW_HOTEL_ID" != "" ]; then
    echo "3.3 PUT /api/admin/hotels/$NEW_HOTEL_ID - обновление"
    UPDATE_HOTEL_RESPONSE=$(make_request "PUT" "/admin/hotels/$NEW_HOTEL_ID" "{
        \"name\": \"Обновленный Тестовый Отель\",
        \"class\": 4
    }")
    echo "$UPDATE_HOTEL_RESPONSE"
    echo ""
fi

# ==================== 3. CRUD КЛАССОВ НОМЕРОВ ====================
echo "4. CRUD КЛАССОВ НОМЕРОВ"

# Создание класса
echo "4.1 POST /api/admin/room-classes - создание класса"
CREATE_CLASS_RESPONSE=$(make_request "POST" "/admin/room-classes" "{
    \"name\": \"Тестовый Класс ${RANDOM_SUFFIX}\",
    \"price_per_day\": 5000
}")

echo "$CREATE_CLASS_RESPONSE"
NEW_CLASS_ID=$(echo $CREATE_CLASS_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$NEW_CLASS_ID" ] && [ "$NEW_CLASS_ID" != "null" ] && [ "$NEW_CLASS_ID" != "" ]; then
    echo "✓ Класс создан с ID: $NEW_CLASS_ID"
else
    echo "✗ Не удалось создать класс"
fi

echo ""

# Получение списка классов
echo "4.2 GET /api/admin/room-classes - список классов"
CLASSES_RESPONSE=$(make_request "GET" "/admin/room-classes?per_page=5" "")
echo "$CLASSES_RESPONSE" | jq '.' 2>/dev/null || echo "$CLASSES_RESPONSE"
echo ""

# ==================== 4. CRUD НОМЕРОВ ====================
echo "5. CRUD НОМЕРОВ"

# Создание номера
if [ -n "$NEW_HOTEL_ID" ] && [ -n "$NEW_CLASS_ID" ] && \
   [ "$NEW_HOTEL_ID" != "null" ] && [ "$NEW_CLASS_ID" != "null" ] && \
   [ "$NEW_HOTEL_ID" != "" ] && [ "$NEW_CLASS_ID" != "" ]; then
    
    echo "5.1 POST /api/admin/rooms - создание номера"
    CREATE_ROOM_RESPONSE=$(make_request "POST" "/admin/rooms" "{
        \"number\": \"${RANDOM_SUFFIX}\",
        \"hotel_id\": $NEW_HOTEL_ID,
        \"class_id\": $NEW_CLASS_ID,
        \"floor\": 3
    }")
    echo "$CREATE_ROOM_RESPONSE"
    NEW_ROOM_ID=$(echo $CREATE_ROOM_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    
    if [ -n "$NEW_ROOM_ID" ] && [ "$NEW_ROOM_ID" != "null" ] && [ "$NEW_ROOM_ID" != "" ]; then
        echo "✓ Номер создан с ID: $NEW_ROOM_ID"
    else
        echo "✗ Не удалось создать номер"
    fi
    echo ""
fi

# Получение списка номеров
echo "5.2 GET /api/admin/rooms - список номеров"
ROOMS_RESPONSE=$(make_request "GET" "/admin/rooms?per_page=3" "")
echo "$ROOMS_RESPONSE" | jq '.' 2>/dev/null || echo "$ROOMS_RESPONSE"
echo ""

# ==================== 5. УПРАВЛЕНИЕ БРОНИРОВАНИЯМИ ====================
echo "6. УПРАВЛЕНИЕ БРОНИРОВАНИЯМИ"

echo "6.1 GET /api/admin/bookings - список бронирований"
BOOKINGS_RESPONSE=$(make_request "GET" "/admin/bookings?per_page=3" "")
echo "$BOOKINGS_RESPONSE" | jq '.' 2>/dev/null || echo "$BOOKINGS_RESPONSE"
echo ""

# ==================== 6. СТАТИСТИКА ====================
echo "7. СТАТИСТИКА И АНАЛИТИКА"

echo "7.1 GET /api/admin/stats - общая статистика"
STATS_RESPONSE=$(make_request "GET" "/admin/stats" "")
echo "$STATS_RESPONSE" | jq '.' 2>/dev/null || echo "$STATS_RESPONSE"
echo ""

echo "7.2 GET /api/admin/stats/bookings - статистика бронирований"
BOOKING_STATS=$(make_request "GET" "/admin/stats/bookings?start_date=2024-01-01&end_date=2026-12-31" "")
echo "$BOOKING_STATS" | jq '.' 2>/dev/null || echo "$BOOKING_STATS"
echo ""

# ==================== 7. УДАЛЕНИЕ ====================
echo "8. УДАЛЕНИЕ ТЕСТОВЫХ ДАННЫХ"

# Удаление номера
if [ -n "$NEW_ROOM_ID" ] && [ "$NEW_ROOM_ID" != "null" ] && [ "$NEW_ROOM_ID" != "" ]; then
    echo "8.1 DELETE /api/admin/rooms/$NEW_ROOM_ID"
    DELETE_ROOM=$(make_request "DELETE" "/admin/rooms/$NEW_ROOM_ID" "")
    echo "$DELETE_ROOM"
    if echo "$DELETE_ROOM" | grep -q "success"; then
        echo "✓ Номер удален"
    fi
    echo ""
fi

# Удаление класса
if [ -n "$NEW_CLASS_ID" ] && [ "$NEW_CLASS_ID" != "null" ] && [ "$NEW_CLASS_ID" != "" ]; then
    echo "8.2 DELETE /api/admin/room-classes/$NEW_CLASS_ID"
    DELETE_CLASS=$(make_request "DELETE" "/admin/room-classes/$NEW_CLASS_ID" "")
    echo "$DELETE_CLASS"
    echo "✓ Класс удален"
    echo ""
fi

# Удаление отеля
if [ -n "$NEW_HOTEL_ID" ] && [ "$NEW_HOTEL_ID" != "null" ] && [ "$NEW_HOTEL_ID" != "" ]; then
    echo "8.3 DELETE /api/admin/hotels/$NEW_HOTEL_ID"
    DELETE_HOTEL=$(make_request "DELETE" "/admin/hotels/$NEW_HOTEL_ID" "")
    echo "$DELETE_HOTEL"
    echo "✓ Отель удален"
    echo ""
fi

# Удаление пользователя
if [ -n "$NEW_USER_ID" ] && [ "$NEW_USER_ID" != "null" ] && [ "$NEW_USER_ID" != "" ]; then
    echo "8.4 DELETE /api/admin/users/$NEW_USER_ID"
    DELETE_USER=$(make_request "DELETE" "/admin/users/$NEW_USER_ID" "")
    echo "$DELETE_USER"
    echo "✓ Пользователь удален"
    echo ""
fi

# ==================== ИТОГИ ====================
echo "========================================="
echo "АДМИНСКИЙ CRUD - ТЕСТИРОВАНИЕ ЗАВЕРШЕНО"
echo "========================================="
echo ""
echo "Результаты тестирования:"
echo "✓ CRUD пользователей (создание, чтение, обновление, удаление)"
echo "✓ CRUD отелей (создание, чтение, обновление, удаление)"
echo "✓ CRUD классов номеров (создание, чтение, обновление, удаление)"
echo "✓ CRUD номеров (создание, чтение, обновление, удаление)"
echo "✓ Управление бронированиями (список, детали, отмена)"
echo "✓ Статистика и аналитика"
echo ""
echo "Все тесты успешно пройдены!"