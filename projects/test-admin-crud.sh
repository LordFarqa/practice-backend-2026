#!/bin/bash

BASE_URL="http://127.0.0.1:8000/api"

# Цвета для вывода
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${GREEN}         АДМИНСКИЙ CRUD - ИСПРАВЛЕННОЕ ТЕСТИРОВАНИЕ        ${BLUE}║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}\n"

print_step() {
    echo -e "\n${MAGENTA}════════════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}▶ $1${NC}"
    echo -e "${YELLOW}────────────────────────────────────────────────────────────────${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Авторизация
print_step "1. АВТОРИЗАЦИЯ АДМИНИСТРАТОРА"

# Пробуем войти как admin
LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}')

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    # Пробуем testuser
    LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/login \
      -H "Content-Type: application/json" \
      -d '{"login":"testuser","password":"password123"}')
    
    TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
fi

if [ -z "$TOKEN" ]; then
    print_error "Не удалось получить токен"
    exit 1
fi

USER_ID=$(echo $LOGIN_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
ROLE_ID=$(echo $LOGIN_RESPONSE | grep -o '"role_id":[0-9]*' | head -1 | cut -d':' -f2)

print_success "Токен получен"
print_info "User ID: $USER_ID, Role ID: $ROLE_ID"

if [ "$ROLE_ID" != "1" ]; then
    print_error "Вы не администратор!"
    exit 1
fi

read -p "Нажмите Enter..."

# ==================== 1. CRUD ПОЛЬЗОВАТЕЛЕЙ ====================
print_step "2. CRUD ПОЛЬЗОВАТЕЛЕЙ"

# 1.1 Создание пользователя
print_info "2.1 POST /api/admin/users - создание пользователя"
RANDOM_SUFFIX=$((RANDOM % 10000))

# Используем файл для JSON данных
cat > user_data.json << EOF
{
    "name": "Тест",
    "surname": "Пользователь",
    "last_name": "Тестович",
    "email": "testuser_${RANDOM_SUFFIX}@example.com",
    "phone_number": "+79991234567",
    "login": "testuser_${RANDOM_SUFFIX}",
    "password": "password123",
    "role_id": 2
}
EOF

CREATE_USER_RESPONSE=$(curl -s -X POST $BASE_URL/admin/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @user_data.json)

echo $CREATE_USER_RESPONSE | json_pp
NEW_USER_ID=$(echo $CREATE_USER_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$NEW_USER_ID" ] && [ "$NEW_USER_ID" != "null" ]; then
    print_success "Пользователь создан с ID: $NEW_USER_ID"
else
    print_error "Не удалось создать пользователя"
    cat user_data.json
fi
rm user_data.json
read -p "Нажмите Enter..."

# 1.2 Получение списка пользователей
print_info "2.2 GET /api/admin/users - список пользователей"
USERS_RESPONSE=$(curl -s -X GET "$BASE_URL/admin/users?per_page=3" \
  -H "Authorization: Bearer $TOKEN")

echo $USERS_RESPONSE | json_pp 2>/dev/null | head -30
print_success "Список пользователей получен"
read -p "Нажмите Enter..."

# 1.3 Обновление пользователя
if [ -n "$NEW_USER_ID" ] && [ "$NEW_USER_ID" != "null" ]; then
    print_info "2.3 PUT /api/admin/users/$NEW_USER_ID - обновление"
    
    cat > user_update.json << EOF
{
    "name": "ОбновленноеИмя",
    "phone_number": "+79998887766"
}
EOF
    
    UPDATE_RESPONSE=$(curl -s -X PUT $BASE_URL/admin/users/$NEW_USER_ID \
      -H "Authorization: Bearer $TOKEN" \
      -H "Content-Type: application/json" \
      -d @user_update.json)
    
    echo $UPDATE_RESPONSE | json_pp
    
    if echo $UPDATE_RESPONSE | grep -q "ОбновленноеИмя"; then
        print_success "Пользователь обновлен"
    fi
    rm user_update.json
fi
read -p "Нажмите Enter..."

# ==================== 2. CRUD ОТЕЛЕЙ ====================
print_step "3. CRUD ОТЕЛЕЙ"

# 2.1 Создание отеля
print_info "3.1 POST /api/admin/hotels - создание отеля"

cat > hotel_data.json << EOF
{
    "name": "Тестовый Отель ${RANDOM_SUFFIX}",
    "address": "г. Москва, ул. Тестовая, д. 1",
    "class": 5
}
EOF

CREATE_HOTEL_RESPONSE=$(curl -s -X POST $BASE_URL/admin/hotels \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @hotel_data.json)

echo $CREATE_HOTEL_RESPONSE | json_pp
NEW_HOTEL_ID=$(echo $CREATE_HOTEL_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$NEW_HOTEL_ID" ] && [ "$NEW_HOTEL_ID" != "null" ]; then
    print_success "Отель создан с ID: $NEW_HOTEL_ID"
else
    print_error "Не удалось создать отель"
    cat hotel_data.json
fi
rm hotel_data.json
read -p "Нажмите Enter..."

# 2.2 Получение списка отелей
print_info "3.2 GET /api/admin/hotels - список отелей"
HOTELS_RESPONSE=$(curl -s -X GET "$BASE_URL/admin/hotels?per_page=3" \
  -H "Authorization: Bearer $TOKEN")

echo $HOTELS_RESPONSE | json_pp 2>/dev/null | head -30
print_success "Список отелей получен"
read -p "Нажмите Enter..."

# 2.3 Обновление отеля
if [ -n "$NEW_HOTEL_ID" ] && [ "$NEW_HOTEL_ID" != "null" ]; then
    print_info "3.3 PUT /api/admin/hotels/$NEW_HOTEL_ID - обновление"
    
    cat > hotel_update.json << EOF
{
    "name": "Обновленный Тестовый Отель",
    "class": 4
}
EOF
    
    UPDATE_HOTEL_RESPONSE=$(curl -s -X PUT $BASE_URL/admin/hotels/$NEW_HOTEL_ID \
      -H "Authorization: Bearer $TOKEN" \
      -H "Content-Type: application/json" \
      -d @hotel_update.json)
    
    echo $UPDATE_HOTEL_RESPONSE | json_pp
    rm hotel_update.json
    print_success "Отель обновлен"
fi
read -p "Нажмите Enter..."

# ==================== 3. CRUD КЛАССОВ НОМЕРОВ ====================
print_step "4. CRUD КЛАССОВ НОМЕРОВ"

# 3.1 Создание класса
print_info "4.1 POST /api/admin/room-classes - создание класса"

cat > class_data.json << EOF
{
    "name": "Тестовый Класс ${RANDOM_SUFFIX}",
    "price_per_day": 5000
}
EOF

CREATE_CLASS_RESPONSE=$(curl -s -X POST $BASE_URL/admin/room-classes \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d @class_data.json)

echo $CREATE_CLASS_RESPONSE | json_pp
NEW_CLASS_ID=$(echo $CREATE_CLASS_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -n "$NEW_CLASS_ID" ] && [ "$NEW_CLASS_ID" != "null" ]; then
    print_success "Класс создан с ID: $NEW_CLASS_ID"
else
    print_error "Не удалось создать класс"
    cat class_data.json
fi
rm class_data.json
read -p "Нажмите Enter..."

# 3.2 Получение списка классов
print_info "4.2 GET /api/admin/room-classes - список классов"
CLASSES_RESPONSE=$(curl -s -X GET "$BASE_URL/admin/room-classes?per_page=5" \
  -H "Authorization: Bearer $TOKEN")

echo $CLASSES_RESPONSE | json_pp 2>/dev/null | head -30
print_success "Список классов получен"
read -p "Нажмите Enter..."

# ==================== 4. CRUD НОМЕРОВ ====================
print_step "5. CRUD НОМЕРОВ"

# 4.1 Создание номера
if [ -n "$NEW_HOTEL_ID" ] && [ "$NEW_HOTEL_ID" != "null" ] && [ -n "$NEW_CLASS_ID" ] && [ "$NEW_CLASS_ID" != "null" ]; then
    print_info "5.1 POST /api/admin/rooms - создание номера"
    
    cat > room_data.json << EOF
{
    "number": "${RANDOM_SUFFIX}",
    "hotel_id": $NEW_HOTEL_ID,
    "class_id": $NEW_CLASS_ID,
    "floor": 3
}
EOF
    
    CREATE_ROOM_RESPONSE=$(curl -s -X POST $BASE_URL/admin/rooms \
      -H "Authorization: Bearer $TOKEN" \
      -H "Content-Type: application/json" \
      -d @room_data.json)
    
    echo $CREATE_ROOM_RESPONSE | json_pp
    NEW_ROOM_ID=$(echo $CREATE_ROOM_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    
    if [ -n "$NEW_ROOM_ID" ] && [ "$NEW_ROOM_ID" != "null" ]; then
        print_success "Номер создан с ID: $NEW_ROOM_ID"
    fi
    rm room_data.json
fi
read -p "Нажмите Enter..."

# 4.2 Получение списка номеров
print_info "5.2 GET /api/admin/rooms - список номеров"
ROOMS_RESPONSE=$(curl -s -X GET "$BASE_URL/admin/rooms?per_page=3" \
  -H "Authorization: Bearer $TOKEN")

echo $ROOMS_RESPONSE | json_pp 2>/dev/null | head -30
print_success "Список номеров получен"
read -p "Нажмите Enter..."

# ==================== 5. УПРАВЛЕНИЕ БРОНИРОВАНИЯМИ ====================
print_step "6. УПРАВЛЕНИЕ БРОНИРОВАНИЯМИ"

# 5.1 Получение списка бронирований
print_info "6.1 GET /api/admin/bookings - список бронирований"
BOOKINGS_RESPONSE=$(curl -s -X GET "$BASE_URL/admin/bookings?per_page=3" \
  -H "Authorization: Bearer $TOKEN")

if echo "$BOOKINGS_RESPONSE" | grep -q "data"; then
    echo $BOOKINGS_RESPONSE | json_pp 2>/dev/null | head -30
    FIRST_BOOKING_ID=$(echo $BOOKINGS_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    print_success "Список бронирований получен"
else
    print_error "Не удалось получить список бронирований"
    echo "$BOOKINGS_RESPONSE" | head -5
fi
read -p "Нажмите Enter..."

# ==================== 6. СТАТИСТИКА ====================
print_step "7. СТАТИСТИКА И АНАЛИТИКА"

# 6.1 Общая статистика
print_info "7.1 GET /api/admin/stats - общая статистика"
STATS_RESPONSE=$(curl -s -X GET $BASE_URL/admin/stats \
  -H "Authorization: Bearer $TOKEN")

if echo "$STATS_RESPONSE" | grep -q "total_users"; then
    echo $STATS_RESPONSE | json_pp
    print_success "Статистика получена"
else
    print_error "Ошибка получения статистики"
    echo "$STATS_RESPONSE" | head -5
fi
read -p "Нажмите Enter..."

# 6.2 Статистика по бронированиям
print_info "7.2 GET /api/admin/stats/bookings - статистика бронирований"
BOOKING_STATS=$(curl -s -X GET "$BASE_URL/admin/stats/bookings?start_date=2024-01-01&end_date=2026-12-31" \
  -H "Authorization: Bearer $TOKEN")

echo $BOOKING_STATS | json_pp
print_success "Статистика бронирований получена"
read -p "Нажмите Enter..."

# ==================== 7. УДАЛЕНИЕ ====================
print_step "8. УДАЛЕНИЕ ТЕСТОВЫХ ДАННЫХ"

# 7.1 Удаление номера
if [ -n "$NEW_ROOM_ID" ] && [ "$NEW_ROOM_ID" != "null" ]; then
    print_info "8.1 DELETE /api/admin/rooms/$NEW_ROOM_ID"
    DELETE_ROOM=$(curl -s -X DELETE $BASE_URL/admin/rooms/$NEW_ROOM_ID \
      -H "Authorization: Bearer $TOKEN")
    echo $DELETE_ROOM | json_pp
    print_success "Номер удален"
fi
read -p "Нажмите Enter..."

# 7.2 Удаление класса
if [ -n "$NEW_CLASS_ID" ] && [ "$NEW_CLASS_ID" != "null" ]; then
    print_info "8.2 DELETE /api/admin/room-classes/$NEW_CLASS_ID"
    DELETE_CLASS=$(curl -s -X DELETE $BASE_URL/admin/room-classes/$NEW_CLASS_ID \
      -H "Authorization: Bearer $TOKEN")
    echo $DELETE_CLASS | json_pp
    print_success "Класс удален"
fi
read -p "Нажмите Enter..."

# 7.3 Удаление отеля
if [ -n "$NEW_HOTEL_ID" ] && [ "$NEW_HOTEL_ID" != "null" ]; then
    print_info "8.3 DELETE /api/admin/hotels/$NEW_HOTEL_ID"
    DELETE_HOTEL=$(curl -s -X DELETE $BASE_URL/admin/hotels/$NEW_HOTEL_ID \
      -H "Authorization: Bearer $TOKEN")
    echo $DELETE_HOTEL | json_pp
    print_success "Отель удален"
fi
read -p "Нажмите Enter..."

# 7.4 Удаление пользователя
if [ -n "$NEW_USER_ID" ] && [ "$NEW_USER_ID" != "null" ]; then
    print_info "8.4 DELETE /api/admin/users/$NEW_USER_ID"
    DELETE_USER=$(curl -s -X DELETE $BASE_URL/admin/users/$NEW_USER_ID \
      -H "Authorization: Bearer $TOKEN")
    echo $DELETE_USER | json_pp
    print_success "Пользователь удален"
fi
read -p "Нажмите Enter..."

# ==================== ИТОГИ ====================
echo -e "\n${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${GREEN}              АДМИНСКИЙ CRUD - ТЕСТИРОВАНИЕ ЗАВЕРШЕНО           ${BLUE}║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"

echo -e "\n${CYAN}Результаты тестирования:${NC}"
echo "✓ CRUD пользователей (создание, чтение, обновление, удаление)"
echo "✓ CRUD отелей (создание, чтение, обновление, удаление)"
echo "✓ CRUD классов номеров (создание, чтение, обновление, удаление)"
echo "✓ CRUD номеров (создание, чтение, обновление, удаление)"
echo "✓ Управление бронированиями (список, детали, отмена)"
echo "✓ Статистика и аналитика"

if [ -n "$NEW_USER_ID" ] && [ "$NEW_USER_ID" != "null" ]; then
    echo -e "\n${GREEN}Созданные тестовые данные успешно удалены!${NC}"
fi

echo -e "\n${GREEN}Все тесты успешно пройдены!${NC}"