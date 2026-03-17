<?php
namespace App\Dto\Booking;

class SearchCriteriaDto
{
    private readonly ?string $date;
    private readonly ?string $start_time;
    private readonly ?string $end_time;
    private readonly ?array $filters;
    private readonly int $per_page;
    private readonly int $page;

    public function __construct(array $data)
    {
        $this->date = $data['date'] ?? null;
        $this->start_time = $data['start_time'] ?? null;
        $this->end_time = $data['end_time'] ?? null;
        $this->filters = $data['filters'] ?? null;
        $this->per_page = $data['per_page'] ?? 15;
        $this->page = $data['page'] ?? 1;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getStartTime(): ?string
    {
        return $this->start_time;
    }

    public function getEndTime(): ?string
    {
        return $this->end_time;
    }

    public function getFilters(): ?array
    {
        return $this->filters;
    }

    public function getPerPage(): int
    {
        return $this->per_page;
    }

    public function getPage(): int
    {
        return $this->page;
    }
}