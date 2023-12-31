<?php

namespace ExposureSoftware\Versioner;

use ExposureSoftware\Versioner\Concerns\Comparisons;
use Stringable;

class VersionString implements Stringable
{
    use Comparisons;

    private int $major = 0;
    private int $minor = 0;
    private int $patch = 0;

    public readonly string $original;

    private readonly ?string $prefix;

    private ?string $build;

    private ?string $suffix;
    private bool $preserveSuffix;

    public function __construct(
        string $version,
    )
    {
        $this->original = $version;
        $this->prefix = preg_replace('/\d.*$/', '', $version);
        [$version, $this->build] = array_pad(explode('+', $version, 2), 2, null);
        [$version, $this->suffix] = array_pad(explode('-', $version, 2), 2, null);

        [$this->major, $this->minor, $this->patch] = array_pad(
            array_map(
                fn(string $segment) => (int)$segment,
                explode('.', preg_replace('/[^0-9.]*([0-9.]+).*/', '$1', $version))
            ),
            3,
            0
        );
        $this->preserveSuffix = false;
    }

    public static function original(string $version): static
    {
        return new static($version);
    }

    public static function incrementMinor(string $original): string
    {
        return (new static($original))->increment(VersionSegment::MINOR);
    }

    public static function incrementMajor(string $original): string
    {
        return (new static($original))->increment(VersionSegment::MAJOR);
    }

    public static function incrementPatch(string $original): string
    {
        return (new static($original))->increment(VersionSegment::PATCH);
    }

    public function increment(VersionSegment $segment): static
    {
        $this->clearExtensions();

        match ($segment) {
            VersionSegment::MAJOR => $this->set(++$this->major, 0, 0),
            VersionSegment::MINOR => $this->set($this->major, ++$this->minor, 0),
            VersionSegment::PATCH => $this->patch++
        };

        return $this;
    }

    public function preserveSuffix(): static
    {
        $this->preserveSuffix = true;

        return $this;
    }

    public function __toString(): string
    {
        return trim(
            trim(
                implode(
                    '.',
                    [
                        $this->prefix . $this->major,
                        $this->minor,
                        $this->patch
                    ]
                )
                . '-'
                . $this->suffix,
                '-'
            )
            . '+'
            . $this->build,
            '.-+'
        );
    }

    protected function set(int $major, int $minor, int $patch): static
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
        return $this;
    }

    protected function clearExtensions(): void
    {
        if ($this->preserveSuffix === false) {
            $this->suffix = null;
        }
        $this->build = null;
    }
}
