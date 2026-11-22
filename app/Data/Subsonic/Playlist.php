<?php

namespace App\Data\Subsonic;

use Illuminate\Support\Collection;

/**
 * A playlist and, when loaded, its playable entries.
 */
final readonly class Playlist
{
    /**
     * @param  Collection<int, Song>  $songs
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $coverArt,
        public string $comment,
        public int $songCount,
        public int $duration,
        public Collection $songs,
    ) {}

    /**
     * Build the playlist summary returned by getPlaylists.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function summaryFromArray(array $payload): self
    {
        return new self(
            id: (string) $payload['id'],
            name: (string) ($payload['name'] ?? 'Senza titolo'),
            coverArt: isset($payload['coverArt']) ? (string) $payload['coverArt'] : null,
            comment: (string) ($payload['comment'] ?? ''),
            songCount: (int) ($payload['songCount'] ?? 0),
            duration: (int) ($payload['duration'] ?? 0),
            songs: collect(),
        );
    }

    /**
     * Build the full playlist returned by getPlaylist.
     *
     * Older Subsonic servers wrap every entry in a "song" key; current Navidrome
     * versions put the track itself in "entry". Both shapes are accepted. An
     * entry without an identifier and a title points at a file that is no longer
     * in the library, so it is dropped because there is nothing to hand to the
     * PSP.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $songs = collect($payload['entry'] ?? [])
            ->map(fn (mixed $entry): ?Song => self::songFromEntry($entry))
            ->filter()
            ->values();

        return new self(
            id: (string) $payload['id'],
            name: (string) ($payload['name'] ?? 'Senza titolo'),
            coverArt: isset($payload['coverArt']) ? (string) $payload['coverArt'] : null,
            comment: (string) ($payload['comment'] ?? ''),
            songCount: (int) ($payload['songCount'] ?? $songs->count()),
            duration: (int) ($payload['duration'] ?? 0),
            songs: $songs,
        );
    }

    private static function songFromEntry(mixed $entry): ?Song
    {
        if (! is_array($entry)) {
            return null;
        }

        $song = isset($entry['song']) && is_array($entry['song'])
            ? $entry['song']
            : $entry;

        return isset($song['id'], $song['title'])
            ? Song::fromArray($song)
            : null;
    }

    /**
     * The label used on the index page and as the feed channel title.
     */
    public function label(): string
    {
        return $this->name;
    }
}
