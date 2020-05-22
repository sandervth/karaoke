<?php

namespace App\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Class Karaoke
 * @package App\Repositories
 */
class Karaoke
{
    public $lyricsPresent = false;
    private $placeHolder = 'Find your Song...';
    private $processedOutput = null;
    private $artist = null;
    private $song = null;
    private $ip = null;

    /**
     * The constructor for the karaoke class binds the users IP address to the object
     *
     * Karaoke constructor.
     * @param string $userIp
     */
    public function __construct(string $userIp)
    {
        $this->ip = $userIp;
    }

    /**
     * Registers the search criteria in the object
     *
     * @param string|null $artist
     * @param string|null $song
     */
    public function setSearchCriteria(?string $artist, ?string $song): void
    {
        $this->artist = $artist;
        $this->song = $song;
    }

    /**
     * Finds and returns any specified errors on the input fields
     *
     * @return array
     */
    public function getInputErrors(): array
    {
        $errors = [];
        if (empty($this->artist)) {
            $errors[] = 'Oops, you forgot to specify an artist';
        }
        if (empty($this->song)) {
            $errors[] = 'Oops, you forgot to specify a song';
        }

        return $errors;
    }

    /**
     * Makes the API call based on the search criteria and parses the API data into a usable array
     *
     * @return array
     * @throws Exception
     */
    public function getLyrics(): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://private-anon-acdc41779e-lyricsovh.apiary-proxy.com/v1/' . $this->artist . '/' . $this->song);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        if (!$output) {
            throw new Exception('Error connecting to lyrics API.');
        }

        $output = json_decode($output, true);

        if (key_exists('error', $output)) {
            $this->processedOutput['error'] = 'No lyrics match your criteria';
        } elseif (key_exists('lyrics', $output)) {
            $lyricasArray = preg_split('/\r\n|\r|\n/', $output['lyrics']);
            $this->processedOutput['lyrics'] = array_filter($lyricasArray, 'strlen');
            $this->setPlaceholder('Get Ready!....');
            $this->lyricsPresent = true;
        } else {
            throw new Exception('Output not formatted as expected');
        }

        return $this->processedOutput;
    }

    /**
     * Sets the placeholder text for the karaoke player
     *
     * @param string $placeholder
     */
    private function setPlaceholder(string $placeholder): void
    {
        $this->placeHolder = $placeholder;
    }

    /**
     * Gets the placeholder text for the karaoke player
     *
     * @return string
     */
    public function getPlaceholder(): string
    {
        return $this->placeHolder;
    }

    /**
     * Converts the lyrics array into JSON output for the JS code
     *
     * @return string
     */
    public function getJsJson(): string
    {
        $jsJson = null;

        foreach ($this->processedOutput['lyrics'] as $key => $line) {
            $jsJson .= '{
            "id" : "' . $key . '",
            "msg"   : "' . $line . '",
            },';
        }

        return $jsJson;
    }

    /**
     * Add row in the search history table
     *
     */
    public function addSearchHistory(): void
    {
        DB::insert('INSERT INTO `searchhistory` (`artist`, `song`, `ip`) VALUES (?, ?, ?)', [$this->artist, $this->song, $this->ip]);
    }

    /**
     * Gets 10 latest searches for the current user/ip
     *
     * @return array
     */
    public function getSearchHistory(): array
    {
        $results = DB::select('SELECT `artist`, `song`, DATE_FORMAT(`timest`, \'%d-%m-%Y %H:%i\') as nicedate FROM `searchhistory` WHERE ip = ? ORDER BY id DESC LIMIT 0,10', [$this->ip]);

        return $results;
    }
}
