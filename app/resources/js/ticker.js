
// todo: there's probably a more elegant way to implement this :)

let currentDateNow = null;

function ticker() {
    setInterval(tick, 1000);
}

function tick() {
    const el = document.getElementById('tickers');

    if (el != null) {
        if(currentDateNow == null)
        {
            const currentServerTime = el.getElementsByClassName('ticker-server')[0].innerHTML;
            currentDateNow = new Date('1970-01-01T' + currentServerTime + 'Z');
        }

        currentDateNow.setUTCSeconds(currentDateNow.getUTCSeconds() + 1);

        const nextHour = new Date(currentDateNow);
        nextHour.setUTCHours(currentDateNow.getUTCHours() + 1);
        nextHour.setMinutes(0);
        nextHour.setSeconds(0);

        el.getElementsByClassName('ticker-server')[0].innerHTML = hms(utc(currentDateNow));
        console.log(nextHour);
        console.log(currentDateNow);
        console.log(nextHour - currentDateNow);
        el.getElementsByClassName('ticker-next-tick')[0].innerHTML = hms(nextHour - currentDateNow);
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
