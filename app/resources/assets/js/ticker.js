
// todo: there's probably a more elegant way to implement this :)

function ticker() {
    tick();
    setInterval(tick, 1000);
}

function tick() {
    const now = new Date();
    const nextHour = new Date();
    nextHour.setHours(now.getHours() + 1);
    nextHour.setMinutes(0);
    nextHour.setSeconds(0);
    const el = document.getElementById('tickers');
    if (el != null) {
        // el.getElementsByClassName('ticker-local')[0].innerHTML = hms(now);
        el.getElementsByClassName('ticker-server')[0].innerHTML = hms(utc(now));
        el.getElementsByClassName('ticker-next-tick')[0].innerHTML = hms(nextHour - now);
    }
}

function hms(value) {
    let hours = 0;
    let minutes = 0;
    let seconds = 0;
    if (value instanceof Date) {
        hours = value.getHours();
        minutes = value.getMinutes();
        seconds = value.getSeconds();
    } else if (typeof value == 'number') {
        value = parseInt(value) / 1000;
        seconds = value % 60;
        minutes = (value - seconds) / 60;
        hours = (value - (minutes * 60) - seconds) / 60;
    }
    return pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
}

function pad(value) {
    return ('00' + value).slice(-2);
}

function utc(date) {
    return new Date(
        date.getUTCFullYear(),
        date.getUTCMonth(),
        date.getUTCDate(),
        date.getUTCHours(),
        date.getUTCMinutes(),
        date.getUTCSeconds()
    )
}

module.exports = ticker;
