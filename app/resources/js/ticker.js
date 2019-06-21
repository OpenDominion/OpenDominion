
// todo: there's probably a more elegant way to implement this :)

function ticker() {
    setInterval(tick, 1000);
}

function tick() {
    const el = document.getElementById('tickers');

    if (el != null) {
        const currentServerTime = el.getElementsByClassName('ticker-server')[0].innerHTML;
        const dateNow = new Date('1970-01-01T' + currentServerTime + 'Z');
        dateNow.setUTCSeconds(dateNow.getUTCSeconds() + 1);

        const nextHour = new Date(dateNow);
        nextHour.setUTCHours(dateNow.getUTCHours() + 1);
        nextHour.setMinutes(0);
        nextHour.setSeconds(0);

        el.getElementsByClassName('ticker-server')[0].innerHTML = hms(utc(dateNow));
        console.log(nextHour);
        console.log(dateNow);
        console.log(nextHour - dateNow);
        el.getElementsByClassName('ticker-next-tick')[0].innerHTML = hms(nextHour - dateNow);
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
