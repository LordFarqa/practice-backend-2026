<?php
    namespace App\Dto\Hotel;

    use App\Models\Hotel;
    use Illuminate\Database\Eloquent\Collection;

class HotelResponseDto
{
    private readonly string $name;
    private readonly string $address;
    private readonly string $class;

    public function __construct(Collection $hotel)
    {
        $this->hotel_data = $hotel;
    }

    public function toArray(): array
    {
        return $this->hotel_data->toArray();
    }
}

?>