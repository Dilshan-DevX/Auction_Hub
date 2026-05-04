protected function isLive(): Attribute
{
    return Attribute::make(
        get: fn () => $this->status === 'live' && now()->between($this->starts_at, $this->ends_at),
    );
}