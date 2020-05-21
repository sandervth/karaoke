function ReplaceContentInContainer(id, content) {
    var container = document.getElementById(id);
    container.innerHTML = content;
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function Lyrics() {
    for (var key in json) {
        await sleep(4500);
        ReplaceContentInContainer('lyricsdiv', json[key].msg);
    }
}
