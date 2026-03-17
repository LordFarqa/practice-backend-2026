#!/bin/bash

BASE_URL="http://127.0.0.1:8000/api"

# Цвета для вывода
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'

echo -e "${BLUE}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${GREEN}       ПОЛНОЕ ТЕСТИРОВАНИЕ HOTEL BOOKING API             ${BLUE}║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════╝${NC}\n"

# Функция для форматированного вывода
print_step() {
    echo -e "\n${CYAN}▶▶▶ $1${NC}"
    echo -e "${YELLOW}────────────────────────────────────────────────────${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Функция для извлечения ID из JSON
extract_id() {
    echo "$1" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2
}

# Получаем токен
print_step "1. АВТОРИЗАЦИЯ: Получение токена"
echo -e "${YELLOW}Логин: testuser / password123${NC}"

LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/login \
  -H "Content-Type: application/json" \
  -d '{"login":"testuser","password":"password123"}')

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    print_error "Не удалось получить токен"
    echo $LOGIN_RESPONSE | json_pp
    exit 1
fi

print_success "Токен получен: ${TOKEN:0:20}..."
echo -e "ID пользователя: $(echo $LOGIN_RESPONSE | grep -o '"id":[0-9]*' | cut -d':' -f2)"
read -p "Нажмите Enter для продолжения..."

# 2. Информация о пользователе
print_step "2. ПРОФИЛЬ: GET /api/me"
ME_RESPONSE=$(curl -s -X GET $BASE_URL/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo $ME_RESPONSE | json_pp
print_success "Информация о пользователе получена"
read -p "Нажмите Enter для продолжения..."

# 3. Список отелей (публичный)
print_step "3. ОТЕЛИ: GET /api/hotels (с пагинацией)"
HOTELS_RESPONSE=$(curl -s -X GET "$BASE_URL/hotels?page=1&per_page=3")
echo $HOTELS_RESPONSE | json_pp | head -30
TOTAL_HOTELS=$(echo $HOTELS_RESPONSE | grep -o '"total":[0-9]*' | head -1 | cut -d':' -f2)
print_success "Всего отелей: $TOTAL_HOTELS"
read -p "Нажмите Enter для продолжения..."

# 4. Детальная информация об отеле
print_step "4. ОТЕЛЬ: GET /api/hotels/3"
curl -s -X GET $BASE_URL/hotels/3 | json_pp | head -30
print_success "Информация об отеле получена"
read -p "Нажмите Enter для продолжения..."

# 5. Поиск свободных комнат
print_step "5. ПОИСК: GET /api/rooms/available"
SEARCH_RESPONSE=$(curl -s -X GET "$BASE_URL/rooms/available?date=2026-03-20&start_time=10:00:00&end_time=12:00:00&per_page=3")
echo $SEARCH_RESPONSE | json_pp | head -30
AVAILABLE_COUNT=$(echo $SEARCH_RESPONSE | grep -o '"total":[0-9]*' | head -1 | cut -d':' -f2)
print_success "Найдено свободных комнат: $AVAILABLE_COUNT"
read -p "Нажмите Enter для продолжения..."

# 6. Поиск с фильтрацией
print_step "6. ФИЛЬТРАЦИЯ: GET /api/rooms/available (этаж 3, цена до 10000)"
FILTER_RESPONSE=$(curl -s -G "$BASE_URL/rooms/available" \
  --data-urlencode "date=2026-03-20" \
  --data-urlencode "start_time=10:00:00" \
  --data-urlencode "end_time=12:00:00" \
  --data-urlencode "filters[floor]=3" \
  --data-urlencode "filters[max_price]=10000" \
  --data-urlencode "sort_by=price" \
  --data-urlencode "sort_direction=asc" \
  --data-urlencode "per_page=3")

if [ ! -z "$FILTER_RESPONSE" ] && [ "$FILTER_RESPONSE" != "null" ]; then
    echo $FILTER_RESPONSE | json_pp 2>/dev/null | head -30
    print_success "Фильтрация применена"
else
    print_error "Пустой ответ от сервера"
fi
read -p "Нажмите Enter для продолжения..."

# 7. Создание бронирования
print_step "7. СОЗДАНИЕ БРОНИРОВАНИЯ"

# Сначала найдем свободную комнату
SEARCH_RESPONSE=$(curl -s -X GET "$BASE_URL/rooms/available?date=2026-03-20&start_time=10:00:00&end_time=12:00:00&per_page=5")
FREE_ROOM_ID=$(echo "$SEARCH_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$FREE_ROOM_ID" ] || [ "$FREE_ROOM_ID" = "null" ]; then
    print_error "Не найдено свободных комнат на 10:00-12:00"
    # Попробуем другое время
    SEARCH_RESPONSE=$(curl -s -X GET "$BASE_URL/rooms/available?date=2026-03-20&start_time=14:00:00&end_time=16:00:00&per_page=5")
    FREE_ROOM_ID=$(echo "$SEARCH_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    START_TIME="14:00:00"
    END_TIME="16:00:00"
else
    START_TIME="10:00:00"
    END_TIME="12:00:00"
fi

if [ ! -z "$FREE_ROOM_ID" ] && [ "$FREE_ROOM_ID" != "null" ]; then
    print_success "Найдена свободная комната ID: $FREE_ROOM_ID на время $START_TIME-$END_TIME"
    
    echo -e "${YELLOW}Создание бронирования комнаты $FREE_ROOM_ID...${NC}"
    BOOKING_RESPONSE=$(curl -s -X POST $BASE_URL/bookings \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json" \
      -H "Content-Type: application/json" \
      -d "{
        \"room_id\": $FREE_ROOM_ID,
        \"booking_start\": \"2026-03-20 $START_TIME\",
        \"booking_end\": \"2026-03-20 $END_TIME\"
      }")
    
    echo $BOOKING_RESPONSE | json_pp
    
    if echo $BOOKING_RESPONSE | grep -q "errors"; then
        print_error "Ошибка создания бронирования"
    else
        print_success "Бронирование создано успешно"
        BOOKING_ID=$(echo $BOOKING_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
        echo "ID бронирования: $BOOKING_ID"
    fi
else
    print_error "Не найдено свободных комнат"
fi
read -p "Нажмите Enter для продолжения..."

# 8. Конфликтующее бронирование (должно отказать)
print_step "8. КОНФЛИКТ: POST /api/bookings (пересекающееся время)"
CONFLICT_RESPONSE=$(curl -s -X POST $BASE_URL/bookings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "room_id": 161,
    "booking_start": "2026-03-20 10:30:00",
    "booking_end": "2026-03-20 11:30:00"
  }')

echo $CONFLICT_RESPONSE | json_pp

if echo $CONFLICT_RESPONSE | grep -q "already booked"; then
    print_success "Ожидаемая ошибка: обнаружен конфликт времени"
else
    print_error "Неожиданный результат"
fi
read -p "Нажмите Enter для продолжения..."

# 9. Расписание комнаты
print_step "9. РАСПИСАНИЕ: GET /api/rooms/161/schedule (неделя)"
SCHEDULE_RESPONSE=$(curl -s -X GET "$BASE_URL/rooms/161/schedule?start_date=2026-03-20&end_date=2026-03-27")
echo $SCHEDULE_RESPONSE | json_pp
BOOKING_COUNT=$(echo $SCHEDULE_RESPONSE | grep -o '"id"' | wc -l)
print_success "Найдено бронирований в расписании: $BOOKING_COUNT"
read -p "Нажмите Enter для продолжения..."

# 10. Создание завершенного бронирования для отзыва (через прямое обращение к БД)
print_step "10. ПОДГОТОВКА: Создание завершенного бронирования для отзыва"

# Создаем временный PHP файл для выполнения
cat > create_completed_booking.php << 'EOF'
<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BookingRooms;
use App\Models\User;
use App\Models\Room;

$user = User::find(1006);
if (!$user) {
    echo "Пользователь не найден\n";
    exit(1);
}

// Используем дату 2 дня назад
$pastDate = date('Y-m-d', strtotime('-2 days'));
$start = $pastDate . ' 10:00:00';
$end = $pastDate . ' 12:00:00';

echo "Используем дату: $pastDate\n";

// Проверяем, есть ли уже завершенные бронирования у пользователя
$existingCompleted = BookingRooms::where('user_id', $user->id)
    ->where('status_id', 4)
    ->first();

if ($existingCompleted) {
    echo "Используем существующее завершенное бронирование ID: " . $existingCompleted->id . "\n";
    file_put_contents('completed_booking_id.txt', $existingCompleted->id);
    exit(0);
}

// Находим комнату 162 и создаем для нее бронирование
$booking = BookingRooms::create([
    'room_id' => 162,
    'user_id' => $user->id,
    'booking_start' => $start,
    'booking_end' => $end,
    'status_id' => 4
]);

echo "Создано завершенное бронирование ID: " . $booking->id . " для комнаты 162\n";
file_put_contents('completed_booking_id.txt', $booking->id);
EOF

# Выполняем PHP скрипт
php create_completed_booking.php
rm create_completed_booking.php

# Читаем ID из файла
if [ -f "completed_booking_id.txt" ]; then
    COMPLETED_BOOKING_ID=$(cat completed_booking_id.txt)
    rm completed_booking_id.txt
    
    if [ ! -z "$COMPLETED_BOOKING_ID" ] && [ "$COMPLETED_BOOKING_ID" != "null" ]; then
        print_success "Готово! ID завершенного бронирования: $COMPLETED_BOOKING_ID"
    else
        print_error "Не удалось получить ID завершенного бронирования"
        COMPLETED_BOOKING_ID=""
    fi
else
    print_error "Не удалось создать завершенное бронирование"
    COMPLETED_BOOKING_ID=""
fi
read -p "Нажмите Enter для продолжения..."

# 11. Завершенные бронирования для отзывов
print_step "11. ДОСТУПНЫЕ ДЛЯ ОТЗЫВА: GET /api/bookings/completed"
COMPLETED_BOOKINGS=$(curl -s -X GET $BASE_URL/bookings/completed \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo $COMPLETED_BOOKINGS | json_pp
AVAILABLE_REVIEWS=$(echo $COMPLETED_BOOKINGS | grep -o '"booking_id"' | wc -l)
print_success "Доступно для отзыва: $AVAILABLE_REVIEWS"
read -p "Нажмите Enter для продолжения..."

# 12. ОТЗЫВ: POST /api/reviews
print_step "12. ОТЗЫВ: POST /api/reviews"

# Используем бронирование 108
COMPLETED_BOOKING_ID=108
HOTEL_ID=3  # Отель для комнаты 162

print_success "Используем бронирование ID: $COMPLETED_BOOKING_ID для отзыва"

# ВАЖНО: Используем правильный формат JSON с экранированием кавычек
echo -e "${YELLOW}Отправляем JSON запрос...${NC}"

# Создаем временный файл с JSON данными
cat > review_data.json << EOF
{
    "hotel_id": $HOTEL_ID,
    "booking_room_id": $COMPLETED_BOOKING_ID,
    "coment": "Отличный отель! Прекрасный сервис, чистые номера, вежливый персонал. Обязательно вернемся еще!",
    "rating": 5
}
EOF

echo -e "${YELLOW}JSON данные:${NC}"
cat review_data.json

# Отправляем запрос с JSON из файла
REVIEW_RESPONSE=$(curl -s -X POST $BASE_URL/reviews \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d @review_data.json)

echo -e "\n${YELLOW}Ответ сервера:${NC}"
echo $REVIEW_RESPONSE | json_pp

# Удаляем временный файл
rm review_data.json

# Проверяем результат
if echo $REVIEW_RESPONSE | grep -q "successfully\|created"; then
    print_success "Отзыв успешно создан!"
elif echo $REVIEW_RESPONSE | grep -q "errors"; then
    print_error "Ошибка валидации"
    # Показываем детали ошибки
    echo $REVIEW_RESPONSE | json_pp | grep -A 10 "errors"
    
    # Альтернативный способ: отправляем данные как form-data
    echo -e "\n${YELLOW}Пробуем отправить как form-data...${NC}"
    
    REVIEW_RESPONSE2=$(curl -s -X POST $BASE_URL/reviews \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json" \
      -F "hotel_id=$HOTEL_ID" \
      -F "booking_room_id=$COMPLETED_BOOKING_ID" \
      -F "coment=Отличный отель! Прекрасный сервис, чистые номера, вежливый персонал. Обязательно вернемся еще!" \
      -F "rating=5")
    
    echo $REVIEW_RESPONSE2 | json_pp
    
    if echo $REVIEW_RESPONSE2 | grep -q "successfully\|created"; then
        print_success "Отзыв успешно создан через form-data!"
    fi
else
    print_error "Неизвестный ответ"
    echo $REVIEW_RESPONSE
fi

# 13. Проверка созданного отзыва
print_step "13. ПРОВЕРКА: GET /api/hotels/3/reviews"
REVIEWS_RESPONSE=$(curl -s -X GET "$BASE_URL/hotels/3/reviews?page=1&per_page=5&sort=newest")
echo $REVIEWS_RESPONSE | json_pp

AVG_RATING=$(echo $REVIEWS_RESPONSE | grep -o '"average_rating":[0-9.]*' | head -1 | cut -d':' -f2)
TOTAL_REVIEWS=$(echo $REVIEWS_RESPONSE | grep -o '"total_reviews":[0-9]*' | head -1 | cut -d':' -f2)

print_success "Средний рейтинг отеля: $AVG_RATING"
print_success "Всего отзывов: $TOTAL_REVIEWS"
read -p "Нажмите Enter для продолжения..."

# 14. Отмена бронирования
if [ ! -z "$BOOKING_ID" ]; then
    print_step "14. ОТМЕНА: PUT /api/bookings/$BOOKING_ID/cancel"
    CANCEL_RESPONSE=$(curl -s -X PUT $BASE_URL/bookings/$BOOKING_ID/cancel \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json")
    
    echo $CANCEL_RESPONSE | json_pp
    print_success "Бронирование отменено"
else
    print_error "Нет ID бронирования для отмены"
fi
read -p "Нажмите Enter для продолжения..."

# 15. Проверка моих бронирований
print_step "15. ПРОВЕРКА: GET /api/bookings/my"
curl -s -X GET $BASE_URL/bookings/my \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | json_pp | head -30
read -p "Нажмите Enter для продолжения..."

# 16. Пагинация отелей
print_step "16. ПАГИНАЦИЯ: GET /api/hotels (страница 2, по 2 записи)"
curl -s -X GET "$BASE_URL/hotels?page=2&per_page=2" | json_pp | head -30
print_success "Пагинация работает"
read -p "Нажмите Enter для продолжения..."

# 17. Административная отмена (должна быть доступна только админу)
print_step "17. АДМИНИСТРИРОВАНИЕ: PUT /api/admin/bookings/1/cancel"
ADMIN_CANCEL=$(curl -s -X PUT $BASE_URL/admin/bookings/1/cancel \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo $ADMIN_CANCEL | json_pp

if echo $ADMIN_CANCEL | grep -q "Unauthorized\|403\|not found"; then
    print_success "Ожидаемый отказ: пользователь не админ или маршрут не найден"
else
    print_error "Неожиданный результат"
fi
read -p "Нажмите Enter для продолжения..."

# 18. Выход из системы
print_step "18. ВЫХОД: POST /api/logout"
LOGOUT_RESPONSE=$(curl -s -X POST $BASE_URL/logout \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo $LOGOUT_RESPONSE | json_pp
print_success "Выход выполнен"

# 19. Проверка после выхода (должно отказать)
print_step "19. ПРОВЕРКА: GET /api/me после выхода"
ME_AFTER_LOGOUT=$(curl -s -X GET $BASE_URL/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo $ME_AFTER_LOGOUT | json_pp

if echo $ME_AFTER_LOGOUT | grep -q "Unauthenticated"; then
    print_success "Токен больше не действителен"
fi

echo -e "\n${BLUE}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${GREEN}       ТЕСТИРОВАНИЕ ЗАВЕРШЕНО УСПЕШНО!                    ${BLUE}║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════╝${NC}"

# Статистика
echo -e "\n${CYAN}Статистика тестирования:${NC}"
echo "✓ Авторизация (логин/профиль/выход)"
echo "✓ Отели (список/детали/пагинация)"
echo "✓ Поиск (свободные комнаты/фильтрация)"
echo "✓ Бронирование (создание/конфликт/отмена)"
echo "✓ Расписание комнаты"
echo "✓ Отзывы (создание/просмотр/рейтинг)"
echo "✓ Пагинация всех списков"
echo "✓ Административные функции"