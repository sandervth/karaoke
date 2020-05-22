function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function ReplaceContentInContainer(id, content) {
    var container = document.getElementById(id);
    container.innerHTML = content;
}

async function Lyrics() {
    for (var key in json) {
        await sleep(4500);
        ReplaceContentInContainer('lyricsdiv', json[key].msg);
    }
}
