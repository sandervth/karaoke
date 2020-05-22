<?php
use App\Repositories\Karaoke;

?>
<link rel="stylesheet" type="text/css" href="{{ url('/css/karaoke.css') }}"/>
<script type="text/javascript" src="{{ URL::asset('js/karaoke.js') }}"></script>

<a href="/karaoke">Reset...</a><br><br>
<?php

$karaokeObj = new Karaoke(request()->ip());

if (!empty($_GET['action']) && $_GET['action'] === 'trySong') {
    $karaokeObj->setSearchCriteria($_GET['artist'], $_GET['song']);

    if ($inputErrors = $karaokeObj->getInputErrors()) {
        foreach ($inputErrors as $error) {
            echo "<span class='error'>" . $error . "</span><br>";
        }
    } else {
        $karaokeObj->addSearchHistory();
        $request = $karaokeObj->getLyrics();

        if (key_exists('error', $request)) {
            echo "<span class='error'>" . $request['error'] . "</span>";
        }
    }
}

//enable the karaoke player if there are lyrics
if($karaokeObj->lyricsPresent):
?>
<div class='nowplaying'>Now playing: <?=$_GET['artist'] . " - " . $_GET['song'];?></div>
<script type='text/javascript'>
    var json = [
        <?=$karaokeObj->getJsJson()?>
    ];
    Lyrics()
</script>
<?php endif;?>

<div id='lyricsdiv' class='lyricsdiv'>
    <?=$karaokeObj->getPlaceholder();?>
</div>

<div class='divblock'>
    <h2>Search...</h2>
    <form method='GET'>
        <input type='hidden' name='action' value='trySong'>
        <label for='artist'>Artist:</label>
        <input id='artist' type='text' name='artist' value='<?=$_GET['artist'] ?? '';?>'><br>
        <label for='song'>Song:</label> <input id='song' type='text' name='song' value='<?=$_GET['song'] ?? '';?>'><br>
        <input type='submit' value='Search'>
    </form>
</div>

<div class='divblock'>
    <?php
    if($results = $karaokeObj->getSearchHistory()):
    ?>
    <h2>Your latest searches</h2>
    <table>
        <tr>
            <th>Artist</th>
            <th>Song</th>
            <th>Date</th>
        </tr>
        <?php
        foreach ($results as $searchHistory):
            echo "<tr><td><a href='?action=trySong&artist=" . $searchHistory->artist . "&song=" . $searchHistory->song . "'>" . $searchHistory->artist . "</a></td><td><a href='?action=trySong&artist=" . $searchHistory->artist . "&song=" . $searchHistory->song . "'>" . $searchHistory->song . "</a></td><td>" . $searchHistory->nicedate . "</td></tr>";
        endforeach;
        ?>
    </table>
    <?php
    endif;
    ?>
</div>
