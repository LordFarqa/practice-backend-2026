Список эндпойнтов

/api
    /auth
    /users
    /hotels
    /rooms
    /room-classes
    /bookings
    /reviews

/api/admin
    /users //готов
    /user/{login}// готов
    /hotels +
        /hotel/{hotel_name}+
        /rooms{hotel_name}
        /room/{room_number}
        /room-classes
        /bookings
