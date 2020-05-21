<?php

namespace App\Repositories;

use Exception;
use Illuminate\Support\Facades\DB;

class Karaoke
{
    public $lyricsPresent = false;
    private $placeHolder = 'Find your Song...';
    private $processedOutput = null;
    private $artist = null;
    private $song = null;
    private $ip = null;

    public function __construct(string $userIp)
    {
        $this->ip = $userIp;
    }

    public function setSearchcriteria(?string $artist, ?string $song): void
    {
        $this->artist = $artist;
        $this->song = $song;
    }

    public function getInputErrors(): array
    {
        $errors=[];
        if(empty($this->artist)){
            $errors[] = 'Oops, you forgot to specify an artist';
        }
        if(empty($this->song)){
            $errors[] = 'Oops, you forgot to specify a song';
        }

        return $errors;
    }

    public function getLyrics(): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://private-anon-acdc41779e-lyricsovh.apiary-proxy.com/v1/'.$this->artist.'/'.$this->song);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        curl_close($ch);

        if(!$output){
            throw new Exception('Error connecting to lyrics API.');
        }

        $output = json_decode($output, true);

        if(key_exists('error', $output)){
            $this->processedOutput['error'] = 'No lyrics match your criteria';
        }
        elseif(key_exists('lyrics', $output)){
            $lyricasArray = preg_split('/\r\n|\r|\n/', $output['lyrics']);
            $this->processedOutput['lyrics'] = array_filter($lyricasArray, 'strlen');
            $this->setPlaceholder('Get Ready!....');
            $this->lyricsPresent = true;
        }
        else{
            throw new Exception('Output not formatted as expected');
        }

        return $this->processedOutput;

    }

    private function setPlaceholder(string $placeholder): void
    {
        $this->placeHolder = $placeholder;
    }

    public function getPlaceholder(): string
    {
        return $this->placeHolder;
    }

    public function getJsJson(): string
    {
        $jsJson = null;

        foreach($this->processedOutput['lyrics'] as $key => $line){
            $jsJson .= '{
            "id" : "'.$key.'",
            "msg"   : "'.$line.'",
            },';
        }

        return $jsJson;
    }

    public function addSearchHistory(): void
    {
        DB::insert('INSERT INTO `searchhistory` (`artist`, `song`, `ip`) VALUES (?, ?, ?)', [$this->artist, $this->song, $this->ip]);
    }

    public function getSearchHistory(): array
    {
        $results = DB::select('SELECT `artist`, `song`, DATE_FORMAT(`timest`, \'%d-%m-%Y %H:%i\') as nicedate FROM `searchhistory` WHERE ip = ? ORDER BY id DESC LIMIT 0,10', [$this->ip]);

        return $results;
    }
}
