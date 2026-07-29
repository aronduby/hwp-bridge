<?php

namespace Traits;

/**
 *
 */
trait HasOtherNumbers
{
    /**
     * @var ?int $number
     */
    public ?int $number;

    /**
     * @var array{ V: int, JV: int, other: int[]} $other_numbers
     */
    public ?array $other_numbers;

    /**
     * @param 'V'|'JV'|null $team
     * @return mixed
     */
    public function getNumber(string $team = null)
    {
        if (!$team || !isset($this->other_numbers[$team])) {
            return $this->number;
        } else {
            return $this->other_numbers[$team];
        }
    }
}