<?php

namespace NotificationChannels\Interfax;

use Illuminate\Support\Arr;

class InterfaxMessage
{
    protected $files;
    protected $stream;
    protected $filename;
    protected $method;
    protected $statusCheck = false;
    public $user;
    public $metadata = [];

    const FILES = 'files';
    const STREAM = 'stream';

    const POLLING_INTERVAL_DEFAULT = 15;
    const POLLING_INTERVAL_MINIMUM = 10;

    public function file(string $file)
    {
        $this->files = Arr::wrap($file);
        $this->method = static::FILES;

        return $this;
    }

    public function files(array $files)
    {
        $this->files = $files;
        $this->method = static::FILES;

        return $this;
    }

    public function stream($stream, string $filename)
    {
        $this->stream = $stream;
        $this->filename = $filename;
        $this->method = static::STREAM;

        return $this;
    }

    public function checkStatus(bool $shouldCheck = true)
    {
        $this->statusCheck = $shouldCheck;

        return $this;
    }

    public function shouldCheckStatus(): bool
    {
        return $this->statusCheck;
    }

    /**
     * Set a user who can be notified in case the fax fails to send.
     *
     * @param  mixed  $notifiable  The user to notify
     * @return InterfaxMessage
     */
    public function user($notifiable)
    {
        $this->user = $notifiable;

        return $this;
    }

    /**
     * Add metadata to the message for logging purposes.
     *
     * @param  array  $data  The data to add to the metadata array
     * @return InterfaxMessage
     */
    public function addMetadata(array $data)
    {
        foreach ($data as $key => $value) {
            $this->metadata[$key] = $value;
        }

        return $this;
    }

    public function makeFiles(): array
    {
        if ($this->method === static::STREAM) {
            return [
                [
                    $this->stream,
                    [
                        'name' => $this->filename,
                        'mime_type' => app('files')->mimeType($this->filename),
                        'chunk_size' => config('services.interfax.chunk_size', 1048576),
                    ],
                ],
            ];
        }

        return array_map('static::setChunkSize', $this->files);
    }

    public function sleep(): void
    {
        $interval = config('services.interfax.interval', static::POLLING_INTERVAL_DEFAULT);
        sleep(max($interval, static::POLLING_INTERVAL_MINIMUM));
    }

    protected static function setChunkSize($file)
    {
        $chunk_size = config('services.interfax.chunk_size', 1048576);

        if (is_string($file)) {
            return [
                'location' => $file,
                'params' => [
                    'chunk_size' => $chunk_size,
                ],
            ];
        } elseif (is_array($file)) {
            $file['params']['chunk_size'] = $chunk_size;
            return $file;
        } else {
            return $file;
        }
    }
}
