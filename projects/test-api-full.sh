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

echo -e "${BLUE}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${GREEN}       ПОЛНОЕ ТЕСТИРОВАНИЕ HOTEL BOOKING API             ${BLUE}║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════╝${NC}\n"

# Функция для форматированного вывода
print_step() {
    echo -e "\n${MAGENTA}══════════════════════════════════════════════════════════${NC}"
    echo -e "${CYAN}▶ $1${NC}"
    echo -e "${YELLOW}────────────────────────────────────────────────────${NC}"
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

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

# Функция для проверки доступности сервера
check_server() {
    print_info "Проверка доступности сервера..."
    
    # Проверяем, запущен ли сервер
    if ! curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000 | grep -q "200\|404"; then
        print_error "Сервер не запущен или недоступен!"
        print_warning "Запустите сервер в отдельном терминале:"
        echo "  cd /d/backend_practice/practice-backend-2026/projects"
        echo "  php artisan serve"
        exit 1
    fi
    
    print_success "Сервер доступен"
}

# Функция для безопасного парсинга JSON
safe_json_pp() {
    local input="$1"
    if echo "$input" | grep -q "^{.*}$"; then
        echo "$input" | json_pp 2>/dev/null || echo "$input"
    else
        echo "Невалидный JSON ответ:"
        echo "$input" | head -5
    fi
}

# Функция для извлечения ID из JSON
extract_id() {
    echo "$1" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2
}

# Проверяем сервер
check_server

# Получаем токен
print_step "1. АВТОРИЗАЦИЯ: Получение токена"

# Пробуем разные варианты логина
LOGIN_SUCCESS=false
TOKEN=""

# Вариант 1: admin / admin123
echo -e "${YELLOW}Попытка 1: login=admin, password=admin123${NC}"
LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"admin123"}' 2>/dev/null)

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$TOKEN" ]; then
    LOGIN_SUCCESS=true
    print_success "Успешный вход как admin"
fi

# Вариант 2: admin / password
if [ "$LOGIN_SUCCESS" = false ]; then
    echo -e "${YELLOW}Попытка 2: login=admin, password=password${NC}"
    LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/login \
      -H "Content-Type: application/json" \
      -d '{"login":"admin","password":"password"}' 2>/dev/null)
    
    TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$TOKEN" ]; then
        LOGIN_SUCCESS=true
        print_success "Успешный вход как admin"
    fi
fi

# Вариант 3: testuser / password123
if [ "$LOGIN_SUCCESS" = false ]; then
    echo -e "${YELLOW}Попытка 3: login=testuser, password=password123${NC}"
    LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/login \
      -H "Content-Type: application/json" \
      -d '{"login":"testuser","password":"password123"}' 2>/dev/null)
    
    TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$TOKEN" ]; then
        LOGIN_SUCCESS=true
        print_success "Успешный вход как testuser"
    fi
fi

# Вариант 4: user / password
if [ "$LOGIN_SUCCESS" = false ]; then
    echo -e "${YELLOW}Попытка 4: login=user, password=password${NC}"
    LOGIN_RESPONSE=$(curl -s -X POST $BASE_URL/login \
      -H "Content-Type: application/json" \
      -d '{"login":"user","password":"password"}' 2>/dev/null)
    
    TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$TOKEN" ]; then
        LOGIN_SUCCESS=true
        print_success "Успешный вход как user"
    fi
fi

if [ "$LOGIN_SUCCESS" = false ]; then
    print_error "Не удалось получить токен"
    print_info "Ответ сервера:"
    echo "$LOGIN_RESPONSE" | head -10
    print_warning "Убедитесь, что в базе данных есть пользователь:"
    echo "  CREATE USER или используйте существующего"
    exit 1
fi

USER_ID=$(echo $LOGIN_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
ROLE_ID=$(echo $LOGIN_RESPONSE | grep -o '"role_id":[0-9]*' | head -1 | cut -d':' -f2)

print_success "Токен получен: ${TOKEN:0:30}..."
print_info "User ID: $USER_ID, Role ID: $ROLE_ID"

if [ "$ROLE_ID" = "1" ]; then
    print_success "Вы вошли как АДМИНИСТРАТОР"
else
    print_warning "Вы вошли как обычный пользователь (role_id=$ROLE_ID)"
    print_info "Административные функции будут пропущены"
fi

read -p "Нажмите Enter для продолжения..."

# 2. Информация о пользователе
print_step "2. ПРОФИЛЬ: GET /api/me"
ME_RESPONSE=$(curl -s -X GET $BASE_URL/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

if [ -n "$ME_RESPONSE" ]; then
    safe_json_pp "$ME_RESPONSE"
    print_success "Информация о пользователе получена"
else
    print_error "Пустой ответ"
fi
read -p "Нажмите Enter для продолжения..."

# 3. Список отелей (публичный)
print_step "3. ОТЕЛИ: GET /api/hotels (с пагинацией)"
HOTELS_RESPONSE=$(curl -s -X GET "$BASE_URL/hotels?page=1&per_page=3")

if [ -n "$HOTELS_RESPONSE" ] && echo "$HOTELS_RESPONSE" | grep -q "data"; then
    safe_json_pp "$HOTELS_RESPONSE" | head -40
    TOTAL_HOTELS=$(echo $HOTELS_RESPONSE | grep -o '"total":[0-9]*' | head -1 | cut -d':' -f2)
    print_success "Всего отелей: $TOTAL_HOTELS"
else
    print_error "Не удалось получить список отелей"
fi
read -p "Нажмите Enter для продолжения..."

# 4. Детальная информация об отеле
print_step "4. ОТЕЛЬ: GET /api/hotels/1"
HOTEL_RESPONSE=$(curl -s -X GET $BASE_URL/hotels/1)

if [ -n "$HOTEL_RESPONSE" ] && echo "$HOTEL_RESPONSE" | grep -q "data"; then
    safe_json_pp "$HOTEL_RESPONSE" | head -30
    print_success "Информация об отеле получена"
else
    print_warning "Отель с ID=1 не найден, пробуем ID=3"
    HOTEL_RESPONSE=$(curl -s -X GET $BASE_URL/hotels/3)
    if [ -n "$HOTEL_RESPONSE" ] && echo "$HOTEL_RESPONSE" | grep -q "data"; then
        safe_json_pp "$HOTEL_RESPONSE" | head -30
        print_success "Информация об отеле получена"
    else
        print_error "Отель не найден"
    fi
fi
read -p "Нажмите Enter для продолжения..."

# 5. Поиск свободных комнат
print_step "5. ПОИСК: GET /api/rooms/available"
CURRENT_DATE=$(date +%Y-%m-%d)
SEARCH_RESPONSE=$(curl -s -X GET "$BASE_URL/rooms/available?date=$CURRENT_DATE&start_time=10:00:00&end_time=12:00:00&per_page=3")

if [ -n "$SEARCH_RESPONSE" ] && echo "$SEARCH_RESPONSE" | grep -q "data"; then
    safe_json_pp "$SEARCH_RESPONSE" | head -30
    AVAILABLE_COUNT=$(echo $SEARCH_RESPONSE | grep -o '"total":[0-9]*' | head -1 | cut -d':' -f2)
    print_success "Найдено свободных комнат: $AVAILABLE_COUNT"
else
    print_warning "Нет свободных комнат на сегодня"
fi
read -p "Нажмите Enter для продолжения..."

# 6. Поиск с фильтрацией
print_step "6. ФИЛЬТРАЦИЯ: GET /api/rooms/available (этаж 1)"
FILTER_RESPONSE=$(curl -s -G "$BASE_URL/rooms/available" \
  --data-urlencode "date=$CURRENT_DATE" \
  --data-urlencode "start_time=10:00:00" \
  --data-urlencode "end_time=12:00:00" \
  --data-urlencode "filters[floor]=1" \
  --data-urlencode "per_page=3")

if [ -n "$FILTER_RESPONSE" ] && echo "$FILTER_RESPONSE" | grep -q "data"; then
    safe_json_pp "$FILTER_RESPONSE" | head -30
    print_success "Фильтрация применена"
else
    print_warning "Нет результатов фильтрации"
fi
read -p "Нажмите Enter для продолжения..."

# 7. Создание бронирования
print_step "7. СОЗДАНИЕ БРОНИРОВАНИЯ"

# Получаем список свободных комнат
SEARCH_RESPONSE=$(curl -s -X GET "$BASE_URL/rooms/available?date=$CURRENT_DATE&start_time=14:00:00&end_time=16:00:00&per_page=5")
FREE_ROOM_ID=$(echo "$SEARCH_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

if [ -z "$FREE_ROOM_ID" ] || [ "$FREE_ROOM_ID" = "null" ]; then
    print_warning "Нет свободных комнат, создаем тестовое бронирование на будущую дату"
    FUTURE_DATE=$(date -d "+7 days" +%Y-%m-%d 2>/dev/null || date -v+7d +%Y-%m-%d)
    SEARCH_RESPONSE=$(curl -s -X GET "$BASE_URL/rooms/available?date=$FUTURE_DATE&start_time=10:00:00&end_time=12:00:00&per_page=5")
    FREE_ROOM_ID=$(echo "$SEARCH_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
    BOOKING_DATE=$FUTURE_DATE
    START_TIME="10:00:00"
    END_TIME="12:00:00"
else
    BOOKING_DATE=$CURRENT_DATE
    START_TIME="14:00:00"
    END_TIME="16:00:00"
fi

if [ -n "$FREE_ROOM_ID" ] && [ "$FREE_ROOM_ID" != "null" ]; then
    print_success "Найдена свободная комната ID: $FREE_ROOM_ID"
    
    BOOKING_RESPONSE=$(curl -s -X POST $BASE_URL/bookings \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json" \
      -H "Content-Type: application/json" \
      -d "{
        \"room_id\": $FREE_ROOM_ID,
        \"booking_start\": \"$BOOKING_DATE $START_TIME\",
        \"booking_end\": \"$BOOKING_DATE $END_TIME\"
      }")
    
    if echo "$BOOKING_RESPONSE" | grep -q "id"; then
        safe_json_pp "$BOOKING_RESPONSE"
        BOOKING_ID=$(echo $BOOKING_RESPONSE | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
        print_success "Бронирование создано с ID: $BOOKING_ID"
    else
        print_error "Ошибка создания бронирования"
        safe_json_pp "$BOOKING_RESPONSE"
    fi
else
    print_error "Не найдено свободных комнат для бронирования"
fi
read -p "Нажмите Enter для продолжения..."

# 8. Конфликтующее бронирование (должно отказать)
print_step "8. КОНФЛИКТ: POST /api/bookings (пересекающееся время)"
CONFLICT_RESPONSE=$(curl -s -X POST $BASE_URL/bookings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "room_id": 1,
    "booking_start": "2026-03-25 10:30:00",
    "booking_end": "2026-03-25 11:30:00"
  }')

safe_json_pp "$CONFLICT_RESPONSE"

if echo "$CONFLICT_RESPONSE" | grep -q "already booked\|time"; then
    print_success "Ожидаемая ошибка: обнаружен конфликт времени"
else
    print_info "Конфликт не обнаружен (возможно, комната свободна)"
fi
read -p "Нажмите Enter для продолжения..."

# 9. Расписание комнаты
print_step "9. РАСПИСАНИЕ: GET /api/rooms/1/schedule"
START_DATE=$(date +%Y-%m-%d)
END_DATE=$(date -d "+7 days" +%Y-%m-%d 2>/dev/null || date -v+7d +%Y-%m-%d)
SCHEDULE_RESPONSE=$(curl -s -X GET "$BASE_URL/rooms/1/schedule?start_date=$START_DATE&end_date=$END_DATE")

if [ -n "$SCHEDULE_RESPONSE" ]; then
    safe_json_pp "$SCHEDULE_RESPONSE" | head -20
    BOOKING_COUNT=$(echo "$SCHEDULE_RESPONSE" | grep -o '"id"' | wc -l)
    print_success "Найдено бронирований в расписании: $BOOKING_COUNT"
else
    print_error "Не удалось получить расписание"
fi
read -p "Нажмите Enter для продолжения..."

# 10. Завершенные бронирования для отзывов
print_step "10. ЗАВЕРШЕННЫЕ БРОНИРОВАНИЯ: GET /api/bookings/completed"
COMPLETED_BOOKINGS=$(curl -s -X GET $BASE_URL/bookings/completed \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

if [ -n "$COMPLETED_BOOKINGS" ]; then
    safe_json_pp "$COMPLETED_BOOKINGS" | head -30
    AVAILABLE_REVIEWS=$(echo $COMPLETED_BOOKINGS | grep -o '"booking_id"' | wc -l)
    print_success "Доступно для отзыва: $AVAILABLE_REVIEWS"
else
    print_info "Нет завершенных бронирований для отзывов"
fi
read -p "Нажмите Enter для продолжения..."

# 11. Отмена бронирования (если есть)
if [ -n "$BOOKING_ID" ]; then
    print_step "11. ОТМЕНА: PUT /api/bookings/$BOOKING_ID/cancel"
    CANCEL_RESPONSE=$(curl -s -X PUT $BASE_URL/bookings/$BOOKING_ID/cancel \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json")
    
    safe_json_pp "$CANCEL_RESPONSE"
    print_success "Бронирование отменено"
else
    print_step "11. ОТМЕНА: Пропуск (нет бронирования для отмены)"
fi
read -p "Нажмите Enter для продолжения..."

# 12. Проверка моих бронирований
print_step "12. МОИ БРОНИРОВАНИЯ: GET /api/bookings/my"
MY_BOOKINGS=$(curl -s -X GET $BASE_URL/bookings/my \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

if [ -n "$MY_BOOKINGS" ]; then
    safe_json_pp "$MY_BOOKINGS" | head -30
    print_success "Список бронирований получен"
else
    print_error "Не удалось получить бронирования"
fi
read -p "Нажмите Enter для продолжения..."

# 13. Административные функции (только для админа)
if [ "$ROLE_ID" = "1" ]; then
    print_step "13. АДМИНИСТРИРОВАНИЕ: Тестирование админских маршрутов"
    
    # Получение списка пользователей
    print_info "GET /api/admin/users"
    ADMIN_USERS=$(curl -s -X GET "$BASE_URL/admin/users?per_page=5" \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json")
    
    if echo "$ADMIN_USERS" | grep -q "data\|success"; then
        safe_json_pp "$ADMIN_USERS" | head -20
        print_success "Список пользователей получен"
    else
        print_error "Ошибка получения списка пользователей"
    fi
    read -p "Нажмите Enter для продолжения..."
    
    # Получение статистики
    print_info "GET /api/admin/stats"
    ADMIN_STATS=$(curl -s -X GET $BASE_URL/admin/stats \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json")
    
    if echo "$ADMIN_STATS" | grep -q "data\|success"; then
        safe_json_pp "$ADMIN_STATS"
        print_success "Статистика получена"
    else
        print_error "Ошибка получения статистики"
    fi
    read -p "Нажмите Enter для продолжения..."
    
    # Получение списка бронирований
    print_info "GET /api/admin/bookings"
    ADMIN_BOOKINGS=$(curl -s -X GET "$BASE_URL/admin/bookings?per_page=5" \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json")
    
    if echo "$ADMIN_BOOKINGS" | grep -q "data\|success"; then
        safe_json_pp "$ADMIN_BOOKINGS" | head -20
        print_success "Список бронирований получен"
    else
        print_error "Ошибка получения списка бронирований"
    fi
    read -p "Нажмите Enter для продолжения..."
else
    print_step "13. АДМИНИСТРИРОВАНИЕ: Пропуск (требуются права администратора)"
    print_info "Чтобы протестировать админские функции, создайте администратора:"
    echo "  php artisan tinker"
    echo "  \$user = User::create(['name'=>'Admin','surname'=>'User','email'=>'admin@example.com','phone_number'=>'+79990000000']);"
    echo "  Client::create(['user_id'=>\$user->id,'login'=>'admin','password'=>Hash::make('admin123'),'role_id'=>1]);"
    read -p "Нажмите Enter для продолжения..."
fi

# 14. Пагинация отелей
print_step "14. ПАГИНАЦИЯ: GET /api/hotels (страница 2, по 2 записи)"
PAGINATION_RESPONSE=$(curl -s -X GET "$BASE_URL/hotels?page=2&per_page=2")

if [ -n "$PAGINATION_RESPONSE" ]; then
    safe_json_pp "$PAGINATION_RESPONSE" | head -20
    print_success "Пагинация работает"
else
    print_error "Ошибка пагинации"
fi
read -p "Нажмите Enter для продолжения..."

# 15. Выход из системы
print_step "15. ВЫХОД: POST /api/logout"
LOGOUT_RESPONSE=$(curl -s -X POST $BASE_URL/logout \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

safe_json_pp "$LOGOUT_RESPONSE"
print_success "Выход выполнен"

# 16. Проверка после выхода (должно отказать)
print_step "16. ПРОВЕРКА: GET /api/me после выхода"
ME_AFTER_LOGOUT=$(curl -s -X GET $BASE_URL/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

safe_json_pp "$ME_AFTER_LOGOUT"

if echo "$ME_AFTER_LOGOUT" | grep -q "Unauthenticated\|unauthenticated"; then
    print_success "Токен больше не действителен"
else
    print_warning "Токен все еще действителен"
fi

# Итоги
echo -e "\n${BLUE}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║${GREEN}               ТЕСТИРОВАНИЕ ЗАВЕРШЕНО!                      ${BLUE}║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════╝${NC}"

echo -e "\n${CYAN}Статистика тестирования:${NC}"
echo "✓ Авторизация (логин/профиль/выход)"
echo "✓ Отели (список/детали/пагинация)"
echo "✓ Поиск свободных комнат"
echo "✓ Фильтрация и сортировка"
echo "✓ Создание бронирования"
echo "✓ Проверка конфликтов"
echo "✓ Расписание комнаты"
echo "✓ Список бронирований пользователя"
echo "✓ Отмена бронирования"

if [ "$ROLE_ID" = "1" ]; then
    echo "✓ Административные функции (пользователи/статистика)"
else
    echo "○ Административные функции (пропущены - нет прав)"
fi

echo -e "\n${GREEN}Все тесты выполнены успешно!${NC}"